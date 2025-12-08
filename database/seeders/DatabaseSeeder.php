<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 勤怠アプリ用の初期データ
        // 例: 管理者ユーザーやテストユーザー
        $this->call([
            UserSeeder::class,
        ]);
    }
}
