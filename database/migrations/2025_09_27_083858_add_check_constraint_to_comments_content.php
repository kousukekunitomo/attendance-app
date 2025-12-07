<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // 既にある場合に備えて一旦DROP（存在しない場合は握りつぶす）
            try {
                DB::statement("ALTER TABLE `comments` DROP CHECK `chk_comments_content_len`");
            } catch (\Throwable $e) {}

            // 1〜255文字のCHECKを付与
            DB::statement(
                "ALTER TABLE `comments`
                 ADD CONSTRAINT `chk_comments_content_len`
                 CHECK (CHAR_LENGTH(`content`) BETWEEN 1 AND 255)"
            );
        }
        // SQLite/その他: ALTERでCHECK追加不可のためスキップ（アプリ側バリデで担保）
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            try {
                DB::statement("ALTER TABLE `comments` DROP CHECK `chk_comments_content_len`");
            } catch (\Throwable $e) {}
        }
        // SQLite/その他は up() で何もしていないので何もしない
    }
};
