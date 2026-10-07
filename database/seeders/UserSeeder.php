<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // 1. ユーザー1（一般）
        User::create([
            'name' => 'ユーザー1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
            'admin_status' => false,
            'email_verified_at' => $now,
        ]);

        // 2. ユーザー2（一般）
        User::create([
            'name' => 'ユーザー2',
            'email' => 'user2@example.com',
            'password' => Hash::make('password'),
            'admin_status' => false,
            'email_verified_at' => $now,
        ]);

        // 3. ユーザー3（管理者）
        User::create([
            'name' => '管理者ユーザー',
            'email' => 'user3@example.com',
            'password' => Hash::make('password'),
            'admin_status' => true,
            'email_verified_at' => $now,
        ]);
    }
}
