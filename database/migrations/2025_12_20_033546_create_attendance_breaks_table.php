<?php

// database/migrations/2025_xx_xx_xxxxxx_create_attendance_breaks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();

            $table->dateTime('break_start_at');       // 休憩開始
            $table->dateTime('break_end_at')->nullable(); // 休憩終了（休憩中は null）

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_breaks');
    }
};
