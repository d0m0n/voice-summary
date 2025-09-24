<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        \App\Models\User::firstOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'Viewer User',
                'email' => 'viewer@example.com',
                'password' => bcrypt('password'),
                'role' => 'viewer',
                'email_verified_at' => now(),
            ]
        );
    }
}
