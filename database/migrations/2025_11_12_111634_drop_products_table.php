<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 参照制約のため存在チェックを分けて安全にDROP
        if (Schema::hasTable('products')) {
            Schema::drop('products'); // Laravelは内部でIF EXISTS扱いに近く安全に落ちます
        }
    }

    public function down(): void
    {
        // 必要なら最小構成で元に戻せるようにしておく（任意）
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name', 255);
                $table->string('description', 1000)->nullable();
                $table->integer('price')->default(0);
                $table->timestamps();
            });
        }
    }
};
