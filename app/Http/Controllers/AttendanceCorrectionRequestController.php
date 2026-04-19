<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrectionRequestBreak;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceCorrectionRequestController extends Controller
{
    /**
     * ユーザー側 申請一覧
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status', 'pending');

        $requests = AttendanceCorrectionRequest::query()
            ->with([
                'user',
                'attendance',
            ])
            ->where('user_id', $user->id)
            ->when(
                $status === 'approved',
                fn ($query) => $query->where('status', 'approved'),
                fn ($query) => $query->where('status', 'pending')
            )
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('stamp_correction_request.index', [
            'requests' => $requests,
            'status' => $status,
        ]);
    }

    /**
     * ユーザー側 申請詳細
     */
    public function show(Request $request, AttendanceCorrectionRequest $stamp_correction_request)
    {
        abort_unless($stamp_correction_request->user_id === $request->user()->id, 403);

        $stamp_correction_request->load([
            'user',
            'attendance',
            'breaks' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        return view('stamp_correction_request.show', [
            'requestItem' => $stamp_correction_request,
        ]);
    }

    /**
     * 勤怠詳細から修正申請作成
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'attendance_id'   => ['required', 'exists:attendances,id'],
            'clock_in'        => ['nullable', 'date_format:H:i'],
            'clock_out'       => ['nullable', 'date_format:H:i'],
            'note'            => ['nullable', 'string', 'max:255'],
            'breaks'          => ['nullable', 'array'],
            'breaks.*.start'  => ['nullable', 'date_format:H:i'],
            'breaks.*.end'    => ['nullable', 'date_format:H:i'],
        ], [
            'attendance_id.required' => '勤怠情報が不正です。',
            'attendance_id.exists'   => '勤怠情報が存在しません。',
            'clock_in.date_format'   => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.date_format'  => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.start.date_format' => '休憩時間が勤務時間外です',
            'breaks.*.end.date_format'   => '休憩時間が勤務時間外です',
            'note.max' => '備考は255文字以内で入力してください',
        ]);

        $attendance = Attendance::with([
            'breaks' => fn ($query) => $query->orderBy('break_start_at'),
            'correctionRequests',
        ])->findOrFail($validated['attendance_id']);

        abort_unless($attendance->user_id === auth()->id(), 403);

        if ($attendance->correctionRequests()->where('status', 'pending')->exists()) {
            return back()
                ->withErrors(['note' => '承認待ちの申請があるため修正申請はできません。'])
                ->withInput();
        }

        return DB::transaction(function () use ($attendance, $validated) {
            $workDate = Carbon::parse($attendance->work_date)->toDateString();

            $clockIn = !empty($validated['clock_in'])
                ? Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $validated['clock_in'])
                : null;

            $clockOut = !empty($validated['clock_out'])
                ? Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $validated['clock_out'])
                : null;

            if ($clockIn && $clockOut && $clockIn->gt($clockOut)) {
                throw ValidationException::withMessages([
                    'clock_out' => '出勤時間もしくは退勤時間が不適切な値です',
                ]);
            }

            $cleanBreaks = collect($validated['breaks'] ?? [])
                ->filter(fn ($break) => !empty($break['start']) || !empty($break['end']))
                ->values();

            foreach ($cleanBreaks as $i => $break) {
                if (empty($break['start']) || empty($break['end'])) {
                    throw ValidationException::withMessages([
                        "breaks.$i.start" => '休憩時間が勤務時間外です',
                    ]);
                }

                $start = Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $break['start']);
                $end   = Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $break['end']);

                if ($start->gt($end)) {
                    throw ValidationException::withMessages([
                        "breaks.$i.end" => '休憩時間が勤務時間外です',
                    ]);
                }

                if ($clockIn && $start->lt($clockIn)) {
                    throw ValidationException::withMessages([
                        "breaks.$i.start" => '休憩時間が勤務時間外です',
                    ]);
                }

                if ($clockOut && $end->gt($clockOut)) {
                    throw ValidationException::withMessages([
                        "breaks.$i.end" => '休憩時間が勤務時間外です',
                    ]);
                }
            }

            $reason = trim((string) ($validated['note'] ?? ''));

            $hasTimeChange = !empty($validated['clock_in']) || !empty($validated['clock_out']);
            $hasBreakChange = $cleanBreaks->isNotEmpty();
            $hasReason = ($reason !== '');

            if (!$hasTimeChange && !$hasBreakChange && !$hasReason) {
                throw ValidationException::withMessages([
                    'note' => '備考を記入してください',
                ]);
            }

            $firstAfterRestStart = $cleanBreaks->get(0)['start'] ?? null;
            $firstAfterRestEnd   = $cleanBreaks->get(0)['end'] ?? null;

            $firstBeforeRestStart = null;
            $firstBeforeRestEnd = null;

            if ($cleanBreaks->isNotEmpty()) {
                $firstBeforeRestStart = optional($attendance->breaks->get(0)?->break_start_at)->format('H:i');
                $firstBeforeRestEnd   = optional($attendance->breaks->get(0)?->break_end_at)->format('H:i');
            }

            $correctionRequest = AttendanceCorrectionRequest::create([
                'attendance_id'      => $attendance->id,
                'user_id'            => auth()->id(),
                'work_date'          => $workDate,

                'before_clock_in'    => optional($attendance->clock_in_at)->format('H:i'),
                'before_clock_out'   => optional($attendance->clock_out_at)->format('H:i'),

                'before_rest_start'  => $firstBeforeRestStart,
                'before_rest_end'    => $firstBeforeRestEnd,

                'after_clock_in'     => $validated['clock_in'] ?? null,
                'after_clock_out'    => $validated['clock_out'] ?? null,

                'after_rest_start'   => $firstAfterRestStart,
                'after_rest_end'     => $firstAfterRestEnd,

                'reason'             => $reason,
                'status'             => 'pending',
            ]);

            $existingBreaks = $attendance->breaks->values();
            $rows = [];

            foreach ($cleanBreaks as $i => $break) {
                $beforeStart = isset($existingBreaks[$i])
                    ? optional($existingBreaks[$i]->break_start_at)->format('H:i')
                    : null;

                $beforeEnd = isset($existingBreaks[$i])
                    ? optional($existingBreaks[$i]->break_end_at)->format('H:i')
                    : null;

                $rows[] = [
                    'attendance_correction_request_id' => $correctionRequest->id,
                    'sort_order'        => $i + 1,
                    'before_rest_start' => $beforeStart,
                    'before_rest_end'   => $beforeEnd,
                    'after_rest_start'  => $break['start'],
                    'after_rest_end'    => $break['end'],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ];
            }

            if (!empty($rows)) {
                AttendanceCorrectionRequestBreak::insert($rows);
            }

            return redirect()
                ->route('attendance.show', $attendance)
                ->with('status', '修正申請を送信しました。');
        });
    }
}