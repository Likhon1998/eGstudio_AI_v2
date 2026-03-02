<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@egen.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
        ]);

        // 2. Create Regular User
        User::create([
            'name' => 'Standard User',
            'email' => 'user@egen.com',
            'password' => Hash::make('12345678'),
            'role' => 'user',
        ]);
    }
}
