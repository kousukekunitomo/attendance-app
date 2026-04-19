<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'note',
    ];

    protected $casts = [
        'work_date'    => 'date',
        'clock_in_at'  => 'datetime',
        'clock_out_at' => 'datetime',
    ];

    protected $appends = [
        'break_minutes',
        'work_minutes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class)->orderBy('break_start_at');
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }

    /**
     * 休憩合計（分）
     * - break_end_at が null の休憩はカウントしない
     */
    public function getBreakMinutesAttribute(): int
    {
        $breaks = $this->relationLoaded('breaks')
            ? $this->breaks
            : $this->breaks()->get();

        $minutes = 0;

        foreach ($breaks as $break) {
            if (!$break->break_start_at || !$break->break_end_at) {
                continue;
            }

            $diff = $break->break_start_at->diffInMinutes($break->break_end_at);

            if ($diff > 0) {
                $minutes += $diff;
            }
        }

        return $minutes;
    }

    /**
     * 実働合計（分） = (退勤 - 出勤) - 休憩
     * - 出勤/退勤が揃っていない時は 0
     */
    public function getWorkMinutesAttribute(): int
    {
        if (!$this->clock_in_at || !$this->clock_out_at) {
            return 0;
        }

        $gross = $this->clock_in_at->diffInMinutes($this->clock_out_at);
        $net = $gross - $this->break_minutes;

        return max(0, $net);
    }

    /**
     * 関連読み込みだけ行う
     * - 現在のテーブルには break_minutes / work_minutes カラムが無いため保存しない
     */
    public function refreshTotals(): void
    {
        $this->loadMissing([
            'breaks' => fn ($q) => $q->orderBy('break_start_at'),
        ]);
    }
}