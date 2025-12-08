<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        'rest_start',
        'rest_end',
        'total_work_minutes',
        'status',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in'  => 'datetime:H:i',
        'clock_out' => 'datetime:H:i',
        'rest_start'=> 'datetime:H:i',
        'rest_end'  => 'datetime:H:i',
    ];

    /** ユーザーとのリレーション */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** 修正申請（複数） */
    public function correctionRequests()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }
}
