<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'ユーザー1',
                'email' => 'user1@example.com',
                'password' => Hash::make('password'),
                'is_admin' => false,
            ],
            // 必要なら追加
            // [
            //   'name' => 'ユーザー2',
            //   'email' => 'user2@example.com',
            //   'password' => Hash::make('password'),
            //   'is_admin' => false,
            // ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => $u['password'],
                    'is_admin' => $u['is_admin'] ?? false,
                ]
            );
        }
    }
}
