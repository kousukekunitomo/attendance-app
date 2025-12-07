<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('name');
        $table->string('brand_name')->nullable();
        $table->text('description');
        $table->foreignId('category_id')->constrained()->onDelete('cascade');
        $table->integer('price');
        $table->tinyInteger('status');
        $table->string('image_path');
        $table->foreignId('condition_id')->constrained()->onDelete('cascade');
        $table->timestamps();
    });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
