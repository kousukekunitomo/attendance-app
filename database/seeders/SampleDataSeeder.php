<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    public function run()
    {
        // 管理者
        User::create([
            'name' => '管理者',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'is_admin' => 1,
        ]);

        // スタッフ5人
        $staffs = collect();

        for ($i = 1; $i <= 5; $i++) {
            $staffs->push(User::create([
                'name' => "スタッフ{$i}",
                'email' => "staff{$i}@test.com",
                'password' => Hash::make('password'),
                'is_admin' => 0,
            ]));
        }

        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        foreach ($staffs as $staff) {
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {

                if (rand(0, 4) === 0) continue;

                $in = $date->copy()->setTime(9, rand(0, 30));
                $out = $date->copy()->setTime(18, rand(0, 30));

                $attendance = Attendance::create([
                    'user_id' => $staff->id,
                    'work_date' => $date->toDateString(),
                    'clock_in_at' => $in,
                    'clock_out_at' => $out,
                    'note' => '通常勤務',
                ]);

                // 休憩
                AttendanceBreak::create([
                    'attendance_id' => $attendance->id,
                    'break_start_at' => $date->copy()->setTime(12, 0),
                    'break_end_at' => $date->copy()->setTime(13, 0),
                ]);

                // 集計
                $attendance->update([
                    'break_minutes' => 60,
                    'work_minutes' => $out->diffInMinutes($in) - 60,
                ]);

                // 申請
                if (rand(0, 5) === 0) {
                    AttendanceCorrectionRequest::create([
                        'attendance_id' => $attendance->id,
                        'user_id' => $staff->id,
                        'work_date' => $date->toDateString(),
                        'before_clock_in' => '09:00',
                        'before_clock_out' => '18:00',
                        'after_clock_in' => '10:00',
                        'after_clock_out' => '18:00',
                        'reason' => '遅刻修正',
                        'status' => 'pending',
                    ]);
                }
            }
        }
    }
}