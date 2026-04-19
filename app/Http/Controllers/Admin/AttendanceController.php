<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    /**
     * 管理者：日別勤怠一覧
     * GET /admin/attendance/list?date=YYYY-MM-DD
     */
    public function index(Request $request)
    {
        $baseDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->startOfDay()
            : Carbon::today();

        if ($baseDate->gt(Carbon::today())) {
            $baseDate = Carbon::today();
        }

        $prevDate = $baseDate->copy()->subDay()->toDateString();
        $nextDate = $baseDate->copy()->addDay();
        $nextIsFuture = $nextDate->gt(Carbon::today());
        $nextDateStr = $nextDate->toDateString();

        $staffs = User::query()
            ->where('is_admin', 0)
            ->orderBy('id')
            ->get(['id', 'name']);

        $attendances = Attendance::query()
            ->with(['breaks' => fn ($q) => $q->orderBy('break_start_at')])
            ->whereDate('work_date', $baseDate->toDateString())
            ->get()
            ->keyBy('user_id');

        $rows = $staffs->map(function ($user) use ($attendances) {
            return [
                'user' => $user,
                'attendance' => $attendances->get($user->id),
            ];
        });

        return view('admin.attendance.index', [
            'baseDate' => $baseDate,
            'prevDate' => $prevDate,
            'nextDate' => $nextDateStr,
            'nextIsFuture' => $nextIsFuture,
            'rows' => $rows,
        ]);
    }

    /**
     * 管理者：勤怠詳細
     * GET /admin/attendance/detail/{attendance}
     */
    public function show(Attendance $attendance)
    {
        $attendance->load([
            'user',
            'breaks' => fn ($q) => $q->orderBy('break_start_at'),
        ]);

        return view('admin.attendance.show', compact('attendance'));
    }

    /**
     * 管理者：勤怠直接修正
     * PUT /admin/attendance/detail/{attendance}
     */
    public function update(Request $request, Attendance $attendance)
    {
        $attendance->load([
            'user',
            'breaks' => fn ($q) => $q->orderBy('break_start_at'),
        ]);

        $validated = $request->validate([
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'breaks' => ['nullable', 'array'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'date_format:H:i'],
            'note' => ['required', 'string', 'max:1000'],
        ], [
            'clock_in.date_format' => '出勤時間は時刻形式で入力してください。',
            'clock_out.date_format' => '退勤時間は時刻形式で入力してください。',
            'breaks.*.start.date_format' => '休憩開始時間は時刻形式で入力してください。',
            'breaks.*.end.date_format' => '休憩終了時間は時刻形式で入力してください。',
            'note.required' => '備考を記入してください。',
            'note.max' => '備考は1000文字以内で入力してください。',
        ]);

        $clockIn = $validated['clock_in'] ?? null;
        $clockOut = $validated['clock_out'] ?? null;
        $breaksInput = $validated['breaks'] ?? [];
        $note = $validated['note'];

        if (!$clockIn || !$clockOut) {
            throw ValidationException::withMessages([
                'clock_in' => '出勤時間もしくは退勤時間が不適切な値です。',
            ]);
        }

        $workDate = Carbon::parse($attendance->work_date)->toDateString();
        $clockInAt = Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $clockIn);
        $clockOutAt = Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $clockOut);

        if ($clockOutAt->lte($clockInAt)) {
            throw ValidationException::withMessages([
                'clock_in' => '出勤時間もしくは退勤時間が不適切な値です。',
            ]);
        }

        $normalizedBreaks = [];

        foreach ($breaksInput as $i => $break) {
            $start = $break['start'] ?? null;
            $end = $break['end'] ?? null;

            if (($start && !$end) || (!$start && $end)) {
                throw ValidationException::withMessages([
                    "breaks.$i.start" => '休憩時間が不適切な値です。',
                ]);
            }

            if (!$start && !$end) {
                continue;
            }

            $breakStartAt = Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $start);
            $breakEndAt = Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $end);

            if ($breakEndAt->lte($breakStartAt)) {
                throw ValidationException::withMessages([
                    "breaks.$i.start" => '休憩時間が不適切な値です。',
                ]);
            }

            if ($breakStartAt->lt($clockInAt) || $breakEndAt->gt($clockOutAt)) {
                throw ValidationException::withMessages([
                    "breaks.$i.start" => '休憩時間が勤務時間外です。',
                ]);
            }

            $normalizedBreaks[] = [
                'break_start_at' => $breakStartAt,
                'break_end_at' => $breakEndAt,
            ];
        }

        usort($normalizedBreaks, function ($a, $b) {
            if ($a['break_start_at']->eq($b['break_start_at'])) {
                return 0;
            }

            return $a['break_start_at']->lt($b['break_start_at']) ? -1 : 1;
        });

        for ($i = 1; $i < count($normalizedBreaks); $i++) {
            $prev = $normalizedBreaks[$i - 1];
            $curr = $normalizedBreaks[$i];

            if ($curr['break_start_at']->lt($prev['break_end_at'])) {
                throw ValidationException::withMessages([
                    "breaks.$i.start" => '休憩時間が重複しています。',
                ]);
            }
        }

        DB::transaction(function () use (
            $attendance,
            $clockInAt,
            $clockOutAt,
            $note,
            $normalizedBreaks
        ) {
            $attendance->update([
                'clock_in_at' => $clockInAt,
                'clock_out_at' => $clockOutAt,
                'note' => $note,
            ]);

            $attendance->breaks()->delete();

            foreach ($normalizedBreaks as $break) {
                $attendance->breaks()->create([
                    'break_start_at' => $break['break_start_at'],
                    'break_end_at' => $break['break_end_at'],
                ]);
            }

            $this->refreshAttendanceTotals($attendance);
        });

        return redirect()
            ->route('admin.attendance.show', $attendance)
            ->with('status', '勤怠を修正しました。');
    }

    /**
     * スタッフ別勤怠
     * GET /admin/attendance/staff/{user}?month=YYYY-MM
     */
    public function staffAttendance(User $user, Request $request)
    {
        $baseMonth = $request->filled('month')
            ? Carbon::parse($request->input('month') . '-01')->startOfMonth()
            : Carbon::today()->startOfMonth();

        $prevMonth = $baseMonth->copy()->subMonth()->format('Y-m');
        $nextMonthCarbon = $baseMonth->copy()->addMonth();
        $nextMonth = $nextMonthCarbon->format('Y-m');
        $nextIsFuture = $nextMonthCarbon->copy()->startOfMonth()->gt(Carbon::today()->startOfMonth());

        $start = $baseMonth->copy()->startOfMonth();
        $end = $baseMonth->copy()->endOfMonth();

        $attendances = Attendance::query()
            ->with(['breaks' => fn ($q) => $q->orderBy('break_start_at')])
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($attendance) => Carbon::parse($attendance->work_date)->toDateString());

        $rows = collect();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $rows->push([
                'date' => $date->copy(),
                'attendance' => $attendances->get($date->toDateString()),
            ]);
        }

        return view('admin.attendance.staff', compact(
            'user',
            'baseMonth',
            'prevMonth',
            'nextMonth',
            'nextIsFuture',
            'rows'
        ));
    }

    /**
     * 管理者：CSV出力（スタッフ別・月別）
     * GET /admin/attendance/csv/export?user_id=1&month=2026-04
     */
    public function exportCsv(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'month' => ['required', 'date_format:Y-m'],
        ], [
            'user_id.required' => 'スタッフ情報が不正です。',
            'user_id.exists' => 'スタッフ情報が存在しません。',
            'month.required' => '対象月が不正です。',
            'month.date_format' => '対象月が不正です。',
        ]);

        $user = User::query()
            ->where('is_admin', 0)
            ->findOrFail($validated['user_id']);

        $baseMonth = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
        $start = $baseMonth->copy()->startOfMonth();
        $end = $baseMonth->copy()->endOfMonth();

        $attendances = Attendance::query()
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn ($attendance) => Carbon::parse($attendance->work_date)->toDateString());

        $filename = 'attendance_' . $user->id . '_' . $baseMonth->format('Y_m') . '.csv';

        return response()->streamDownload(function () use ($start, $end, $attendances) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $attendance = $attendances->get($date->toDateString());

                $clockIn = $attendance?->clock_in_at ? $attendance->clock_in_at->format('H:i') : '';
                $clockOut = $attendance?->clock_out_at ? $attendance->clock_out_at->format('H:i') : '';
                $break = $attendance ? sprintf('%d:%02d', intdiv((int) $attendance->break_minutes, 60), ((int) $attendance->break_minutes) % 60) : '';
                $work = $attendance ? sprintf('%d:%02d', intdiv((int) $attendance->work_minutes, 60), ((int) $attendance->work_minutes) % 60) : '';

                fputcsv($handle, [
                    $date->format('Y-m-d'),
                    $clockIn,
                    $clockOut,
                    $break,
                    $work,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * 管理者：日付指定で勤怠詳細を開く
     */
    public function showByDate(User $user, string $date)
    {
        $workDate = Carbon::parse($date)->toDateString();

        $attendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $workDate)
            ->first();

        if (!$attendance) {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'work_date' => $workDate,
            ]);
        }

        return redirect()->route('admin.attendance.show', $attendance);
    }

    /**
     * 休憩時間・勤務時間を再集計
     */
    private function refreshAttendanceTotals(Attendance $attendance): void
    {
        $attendance->refresh();
        $attendance->load([
            'breaks' => fn ($q) => $q->orderBy('break_start_at'),
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
}