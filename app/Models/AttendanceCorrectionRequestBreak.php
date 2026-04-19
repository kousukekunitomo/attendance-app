<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionRequestBreak extends Model
{
    protected $fillable = [
        'attendance_correction_request_id',
        'sort_order',
        'before_rest_start',
        'before_rest_end',
        'after_rest_start',
        'after_rest_end',
    ];

    public function correctionRequest()
    {
        return $this->belongsTo(AttendanceCorrectionRequest::class, 'attendance_correction_request_id');
    }
}
