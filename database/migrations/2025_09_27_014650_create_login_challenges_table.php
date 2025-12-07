<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('login_challenges', function (Blueprint $table) {
            $table->id();

            // users テーブルへの外部キー
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // 一度きりの検証用トークン（64文字）をユニークに
            $table->string('token', 64)->unique();

            // 監査用情報（任意）
            $table->string('ip')->nullable();
            $table->string('user_agent')->nullable();

            // 有効期限と使用済みフラグ
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();

            $table->timestamps();

            // よく使うクエリ向けの補助インデックス（任意だけど推奨）
            $table->index(['user_id', 'used_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_challenges');
    }
};
