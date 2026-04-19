<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    /**
     * PG03 勤怠打刻画面
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();

        $todayAttendance = Attendance::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $today->toDateString())
            ->with([
                'breaks' => fn ($q) => $q->orderBy('break_start_at'),
            ])
            ->first();

        $status = $this->resolveStatus($todayAttendance);

        $weekdayMap = ['日', '月', '火', '水', '木', '金', '土'];

        return view('attendance.index', [
            'status' => $status,
            'todayAttendance' => $todayAttendance,
            'todayLabel' => $now->format('Y年n月j日'),
            'weekdayLabel' => $weekdayMap[$now->dayOfWeek],
            'currentTime' => $now->format('H:i'),
        ]);
    }

    /**
     * 打刻処理
     */
    public function stamp(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();
        $action = $request->input('action');

        try {
            DB::transaction(function () use ($user, $today, $action) {
                $attendance = Attendance::query()
                    ->where('user_id', $user->id)
                    ->whereDate('work_date', $today->toDateString())
                    ->with([
                        'breaks' => fn ($q) => $q->orderBy('break_start_at'),
                    ])
                    ->lockForUpdate()
                    ->first();

                if (!$attendance) {
                    if ($action !== 'clock_in') {
                        throw new \RuntimeException('出勤前は「出勤」だけ可能です。');
                    }

                    Attendance::create([
                        'user_id' => $user->id,
                        'work_date' => $today->toDateString(),
                        'clock_in_at' => now(),
                    ]);

                    return;
                }

                if ($attendance->clock_out_at) {
                    throw new \RuntimeException('退勤後は打刻できません。');
                }

                $latestBreak = $attendance->breaks->last();
                $onBreak = $latestBreak && $latestBreak->break_end_at === null;

                switch ($action) {
                    case 'clock_in':
                        throw new \RuntimeException('すでに出勤済みです。');

                    case 'break_start':
                        if (!$attendance->clock_in_at) {
                            throw new \RuntimeException('出勤前は休憩開始できません。');
                        }

                        if ($onBreak) {
                            throw new \RuntimeException('すでに休憩中です。');
                        }

                        AttendanceBreak::create([
                            'attendance_id' => $attendance->id,
                            'break_start_at' => now(),
                            'break_end_at' => null,
                        ]);
                        return;

                    case 'break_end':
                        if (!$onBreak) {
                            throw new \RuntimeException('休憩中ではありません。');
                        }

                        $latestBreak->update([
                            'break_end_at' => now(),
                        ]);
                        return;

                    case 'clock_out':
                        if (!$attendance->clock_in_at) {
                            throw new \RuntimeException('出勤前は退勤できません。');
                        }

                        if ($onBreak) {
                            throw new \RuntimeException('休憩中は退勤できません（休憩終了してください）。');
                        }

                        $attendance->update([
                            'clock_out_at' => now(),
                        ]);

                        $attendance->refresh();
                        $attendance->load([
                            'breaks' => fn ($q) => $q->orderBy('break_start_at'),
                        ]);
                        $attendance->refreshTotals();

                        return;

                    default:
                        throw new \RuntimeException('不正な操作です。');
                }
            });

            return redirect()->route('attendance.index');
        } catch (\Throwable $e) {
            return redirect()
                ->route('attendance.index')
                ->withErrors([$e->getMessage()]);
        }
    }

    /**
     * PG04 勤怠一覧画面
     */
    public function list(Request $request)
    {
        $user = $request->user();

        $monthStr = $request->query('month'); // YYYY-MM
        $base = $monthStr
            ? Carbon::createFromFormat('Y-m', $monthStr)->startOfMonth()
            : now()->startOfMonth();

        $start = $base->copy()->startOfMonth();
        $end = $base->copy()->endOfMonth();

        $attendances = Attendance::query()
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy(fn ($attendance) => Carbon::parse($attendance->work_date)->toDateString());

        $days = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();

            $days[] = [
                'date' => $date->copy(),
                'attendance' => $attendances->get($key),
            ];
        }

        return view('attendance.list', [
            'baseMonth' => $base,
            'prevMonth' => $base->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $base->copy()->addMonth()->format('Y-m'),
            'days' => $days,
        ]);
    }

    /**
     * PG05 勤怠詳細画面
     */
    public function show(Request $request, Attendance $attendance)
    {
        abort_unless($attendance->user_id === $request->user()->id, 403);

        $attendance->load([
            'user',
            'breaks' => fn ($q) => $q->orderBy('break_start_at'),
            'correctionRequests' => fn ($q) => $q->latest(),
        ]);

        $date = Carbon::parse($attendance->work_date);

        $breakTotal = sprintf(
            '%d:%02d',
            intdiv((int) $attendance->break_minutes, 60),
            ((int) $attendance->break_minutes) % 60
        );

        $workTotal = sprintf(
            '%d:%02d',
            intdiv((int) $attendance->work_minutes, 60),
            ((int) $attendance->work_minutes) % 60
        );

        $pendingRequest = $attendance->correctionRequests->firstWhere('status', 'pending');

        $breakRows = max(2, $attendance->breaks->count() + 1);

        return view('attendance.show', compact(
            'attendance',
            'date',
            'breakTotal',
            'workTotal',
            'breakRows',
            'pendingRequest'
        ));
    }

    /**
     * 日付指定で勤怠詳細へ遷移
     */
    public function showByDate(Request $request, string $date)
    {
        $user = $request->user();

        try {
            $workDate = Carbon::createFromFormat('Y-m-d', $date)->toDateString();
        } catch (\Throwable $e) {
            abort(404);
        }

        $attendance = Attendance::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'work_date' => $workDate,
            ],
            [
                'clock_in_at' => null,
                'clock_out_at' => null,
                'break_minutes' => 0,
                'work_minutes' => 0,
            ]
        );

        return redirect()->route('attendance.show', $attendance);
    }

    /**
     * PG05 勤怠詳細更新
     */
    public function update(Request $request, Attendance $attendance)
    {
        abort_unless($attendance->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:255'],

            'breaks' => ['array'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'date_format:H:i'],
        ]);

        DB::transaction(function () use ($attendance, $validated) {
            $attendance->load([
                'breaks' => fn ($q) => $q->orderBy('break_start_at'),
            ]);

            $workDate = Carbon::parse($attendance->work_date)->toDateString();

            $clockIn = !empty($validated['clock_in'])
                ? Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $validated['clock_in'])
                : null;

            $clockOut = !empty($validated['clock_out'])
                ? Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $validated['clock_out'])
                : null;

            if ($clockIn && $clockOut && $clockIn->gt($clockOut)) {
                throw ValidationException::withMessages([
                    'clock_out' => '退勤は出勤より後の時刻にしてください。',
                ]);
            }

            $attendance->clock_in_at = $clockIn;
            $attendance->clock_out_at = $clockOut;

            if ($attendance->isFillable('note')) {
                $attendance->note = $validated['note'] ?? null;
            }

            $attendance->save();

            $incomingBreaks = $validated['breaks'] ?? [];
            $existingBreaks = $attendance->breaks->values();

            foreach ($incomingBreaks as $index => $row) {
                $start = $row['start'] ?? null;
                $end = $row['end'] ?? null;

                if (empty($start) && empty($end)) {
                    if (isset($existingBreaks[$index])) {
                        $existingBreaks[$index]->delete();
                    }
                    continue;
                }

                if (empty($start) || empty($end)) {
                    throw ValidationException::withMessages([
                        "breaks.$index.start" => '休憩は開始・終了を両方入力してください。',
                    ]);
                }

                $startAt = Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $start);
                $endAt = Carbon::createFromFormat('Y-m-d H:i', $workDate . ' ' . $end);

                if ($startAt->gt($endAt)) {
                    throw ValidationException::withMessages([
                        "breaks.$index.end" => '休憩終了は休憩開始より後の時刻にしてください。',
                    ]);
                }

                if (isset($existingBreaks[$index])) {
                    $existingBreaks[$index]->update([
                        'break_start_at' => $startAt,
                        'break_end_at' => $endAt,
                    ]);
                } else {
                    AttendanceBreak::create([
                        'attendance_id' => $attendance->id,
                        'break_start_at' => $startAt,
                        'break_end_at' => $endAt,
                    ]);
                }
            }

            $attendance->refresh();
            $attendance->load([
                'breaks' => fn ($q) => $q->orderBy('break_start_at'),
            ]);
            $attendance->refreshTotals();
        });

        return redirect()
            ->route('attendance.show', $attendance)
            ->with('status', '更新しました。');
    }

    /**
     * 当日の状態判定
     */
    private function resolveStatus(?Attendance $attendance): string
    {
        if (!$attendance || !$attendance->clock_in_at) {
            return 'before_work';
        }

        if ($attendance->clock_out_at) {
            return 'after_work';
        }

        $latestBreak = $attendance->breaks->last();
        if ($latestBreak && $latestBreak->break_end_at === null) {
            return 'on_break';
        }

        return 'working';
    }

    /**
     * 本日の打刻履歴（将来拡張用）
     */
    private function buildTodayHistory(?Attendance $attendance): array
    {
        if (!$attendance) {
            return [];
        }

        $history = [];

        if ($attendance->clock_in_at) {
            $history[] = [
                'label' => '出勤',
                'time' => $attendance->clock_in_at->format('H:i'),
            ];
        }

        foreach ($attendance->breaks as $i => $break) {
            $no = $i + 1;

            $history[] = [
                'label' => "休憩開始({$no})",
                'time' => $break->break_start_at->format('H:i'),
            ];

            if ($break->break_end_at) {
                $history[] = [
                    'label' => "休憩終了({$no})",
                    'time' => $break->break_end_at->format('H:i'),
                ];
            }
        }

        if ($attendance->clock_out_at) {
            $history[] = [
                'label' => '退勤',
                'time' => $attendance->clock_out_at->format('H:i'),
            ];
        }

        return $history;
    }
}