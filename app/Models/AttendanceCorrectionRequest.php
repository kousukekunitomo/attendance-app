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
        'work_date'        => 'date',
        'approved_at'      => 'datetime',
        'before_clock_in'  => 'datetime:H:i',
        'before_clock_out' => 'datetime:H:i',
        'before_rest_start'=> 'datetime:H:i',
        'before_rest_end'  => 'datetime:H:i',
        'after_clock_in'   => 'datetime:H:i',
        'after_clock_out'  => 'datetime:H:i',
        'after_rest_start' => 'datetime:H:i',
        'after_rest_end'   => 'datetime:H:i',
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
}
