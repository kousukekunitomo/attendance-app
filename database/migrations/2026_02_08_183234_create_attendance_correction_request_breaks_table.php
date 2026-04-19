<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_correction_request_breaks', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('attendance_correction_request_id');

    $table->unsignedSmallInteger('sort_order')->default(1);

    $table->time('before_rest_start')->nullable();
    $table->time('before_rest_end')->nullable();
    $table->time('after_rest_start')->nullable();
    $table->time('after_rest_end')->nullable();

    $table->timestamps();

    $table->index(['attendance_correction_request_id', 'sort_order'], 'acr_breaks_acr_sort_idx');

    $table->foreign('attendance_correction_request_id', 'acr_breaks_acr_id_fk')
        ->references('id')
        ->on('attendance_correction_requests')
        ->cascadeOnDelete();
});

    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_request_breaks');
    }
};
