<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->date('work_date');

            // 出勤/退勤（正）
            $table->timestamp('clock_in_at')->nullable();
            $table->timestamp('clock_out_at')->nullable();

            // 集計（残すならOK）
            $table->integer('break_minutes')->default(0);
            $table->integer('work_minutes')->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
