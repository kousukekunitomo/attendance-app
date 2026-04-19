<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceCorrectionRequestController extends Controller
{
    /**
     * work_date(例: 2026-02-16) + time(例: 09:00 / 09:00:00) -> datetime
     */
    private function combineDateAndTime($workDate, ?string $time): ?Carbon
    {
        if (!$time) {
            return null;
        }

        return Carbon::parse($workDate)->setTimeFromTimeString($time);
    }

    /**
     * 勤怠の休憩合計・勤務合計を再計算して保存
     */
    private function refreshAttendanceTotals(Attendance $attendance): void
    {
        $attendance->refresh();
        $attendance->load([
            'breaks' => fn ($query) => $query->orderBy('break_start_at'),
        ]);

        $breakMinutes = $attendance->breaks->sum(function ($break) {
            if (!$break->break_start_at || !$break->break_end_at) {
                return 0;
            }

            return $break->break_end_at->diffInMinutes($break->break_start_at);
        });

        $workMinutes = 0;

        if ($attendance->clock_in_at && $attendance->clock_out_at) {
            $workMinutes = $attendance->clock_out_at->diffInMinutes($attendance->clock_in_at) - $breakMinutes;
        }

        $attendance->update([
            'break_minutes' => $breakMinutes,
            'work_minutes' => max(0, $workMinutes),
        ]);
    }

    /**
     * 管理者：申請一覧
     */
    public function index()
    {
        $status = request('status', 'pending');

        $requests = AttendanceCorrectionRequest::query()
            ->with(['user'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.stamp_correction_request.index', compact('requests', 'status'));
    }

    /**
     * 管理者：申請詳細
     */
    public function show(AttendanceCorrectionRequest $request)
    {
        $request->load([
            'user',
            'attendance',
            'breaks' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        return view('admin.stamp_correction_request.show', [
            'requestItem' => $request,
        ]);
    }

    /**
     * 管理者：申請承認
     */
    public function approve(AttendanceCorrectionRequest $request)
    {
        if ($request->status !== 'pending') {
            return back()->with('error', 'この申請はすでに処理済みです。');
        }

        if (
            !$request->after_clock_in &&
            !$request->after_clock_out &&
            !$request->after_rest_start &&
            !$request->after_rest_end &&
            $request->breaks()->count() === 0 &&
            trim((string) $request->reason) === ''
        ) {
            return back()->with('error', '申請内容が空のため承認できません。');
        }

        DB::transaction(function () use ($request) {
            $attendance = Attendance::query()
                ->lockForUpdate()
                ->findOrFail($request->attendance_id);

            $payload = [];

            if ($request->after_clock_in) {
                $payload['clock_in_at'] = $this->combineDateAndTime(
                    $request->work_date,
                    $request->after_clock_in
                );
            }

            if ($request->after_clock_out) {
                $payload['clock_out_at'] = $this->combineDateAndTime(
                    $request->work_date,
                    $request->after_clock_out
                );
            }

            if (!empty($payload)) {
                $attendance->update($payload);
            }

            $breakRows = $request->breaks()
                ->orderBy('sort_order')
                ->get(['after_rest_start', 'after_rest_end']);

            $hasCompatBreak = (bool) $request->after_rest_start;

            if ($breakRows->isNotEmpty() || $hasCompatBreak) {
                AttendanceBreak::where('attendance_id', $attendance->id)->delete();
            }

            if ($breakRows->isNotEmpty()) {
                foreach ($breakRows as $breakRow) {
                    $start = $this->combineDateAndTime(
                        $request->work_date,
                        $breakRow->after_rest_start
                    );

                    $end = $this->combineDateAndTime(
                        $request->work_date,
                        $breakRow->after_rest_end
                    );

                    if ($start) {
                        AttendanceBreak::create([
                            'attendance_id'  => $attendance->id,
                            'break_start_at' => $start,
                            'break_end_at'   => $end,
                        ]);
                    }
                }
            } elseif ($hasCompatBreak) {
                $start = $this->combineDateAndTime(
                    $request->work_date,
                    $request->after_rest_start
                );

                $end = $this->combineDateAndTime(
                    $request->work_date,
                    $request->after_rest_end
                );

                if ($start) {
                    AttendanceBreak::create([
                        'attendance_id'  => $attendance->id,
                        'break_start_at' => $start,
                        'break_end_at'   => $end,
                    ]);
                }
            }

            $this->refreshAttendanceTotals($attendance);

            $request->update([
                'status'      => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });

        return back()->with('success', '申請を承認しました。');
    }

    /**
     * 管理者：申請却下
     */
    public function reject(AttendanceCorrectionRequest $request)
    {
        if ($request->status !== 'pending') {
            return back()->with('error', 'この申請はすでに処理済みです。');
        }

        $request->update([
            'status'      => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', '申請を却下しました。');
    }
}