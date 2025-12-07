<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ダミーユーザー10件を作成（ID: 1〜10）
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'name' => "ユーザー{$i}",
                'email' => "user{$i}@example.com",
                'password' => Hash::make('password'),
            ]);
        }
    }
}
