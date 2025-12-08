<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 勤怠情報テーブル
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            // 利用ユーザー
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // 勤務日（「date」は予約語なので work_date にしておく）
            $table->date('work_date');

            // 打刻情報
            $table->time('clock_in')->nullable();      // 出勤時刻
            $table->time('clock_out')->nullable();     // 退勤時刻
            $table->time('rest_start')->nullable();    // 休憩開始
            $table->time('rest_end')->nullable();      // 休憩終了

            // 勤務時間（分）…後でバッチ or 保存時に計算してもOK
            $table->unsignedInteger('total_work_minutes')->nullable();

            // ステータス：勤務中 / 休憩中 / 退勤済 などを管理する想定
            $table->string('status', 20)->default('not_worked');

            $table->timestamps();

            // ユーザー×日付は1レコードにする想定
            $table->unique(['user_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
