<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SupportStaffSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate([
            'email' => 'support@olodo.edu.ng',
        ], [
            'name' => 'Halima Yusuf',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => UserRole::SupportStaff->value,
        ]);
    }
}
