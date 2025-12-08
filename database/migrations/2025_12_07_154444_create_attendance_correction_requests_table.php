<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 勤怠修正申請テーブル
     */
    public function up(): void
    {
        Schema::create('attendance_correction_requests', function (Blueprint $table) {
            $table->id();

            // 対象の勤怠
            $table->foreignId('attendance_id')
                ->constrained('attendances')
                ->cascadeOnDelete();

            // 申請したユーザー（基本は勤怠の本人）
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // 対象日を持っておくと検索しやすい
            $table->date('work_date');

            // 修正「前」の値
            $table->time('before_clock_in')->nullable();
            $table->time('before_clock_out')->nullable();
            $table->time('before_rest_start')->nullable();
            $table->time('before_rest_end')->nullable();

            // 修正「後」の値
            $table->time('after_clock_in')->nullable();
            $table->time('after_clock_out')->nullable();
            $table->time('after_rest_start')->nullable();
            $table->time('after_rest_end')->nullable();

            // 申請理由（テストケースの「備考」想定）
            $table->text('reason');

            // ステータス: pending / approved / rejected
            $table->string('status', 20)->default('pending');

            // 承認者＆承認日時（未承認なら null）
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_requests');
    }
};
