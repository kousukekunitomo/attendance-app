<?php

// database/migrations/xxxx_xx_xx_xxxxxx_create_category_item_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('category_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->unique(['category_id','item_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('category_item');
    }
};

