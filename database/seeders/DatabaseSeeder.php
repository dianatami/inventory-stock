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
        User::updateOrCreate(
            ['username' => 'omprof0001'],
            [
                'name' => 'Manager OMPROF',
                'email' => 'manager@omprof.test',
                'password' => Hash::make('Manager123!'),
                'role' => 'Manager',
                'is_active' => true,
            ]
        );
    }
}