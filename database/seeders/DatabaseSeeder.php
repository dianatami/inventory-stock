<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'mietime01'],
            [
                'name' => 'Manager Mie Time',
                'email' => 'manager@mietime.test',
                'password' => Hash::make('Manager123!'),
                'role' => 'Manager',
                'is_active' => true,
            ]
        );
    }
}