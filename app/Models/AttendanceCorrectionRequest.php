<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'work_date',

        'before_clock_in',
        'before_clock_out',
        'before_rest_start',
        'before_rest_end',

        'after_clock_in',
        'after_clock_out',
        'after_rest_start',
        'after_rest_end',

        'reason',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'work_date'   => 'date',
        'approved_at' => 'datetime',
    ];

    /** 対象の勤怠 */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /** 申請者（一般ユーザー） */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** 承認者（管理者ユーザー） */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function breaks()
    {
        return $this->hasMany(
            AttendanceCorrectionRequestBreak::class,
            'attendance_correction_request_id'
        );
    }
}
