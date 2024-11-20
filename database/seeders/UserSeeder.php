<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'user_name' => 'admin1',
            'user_email' => 'admin@example.com',
            'user_phone' => '1234567890',
            'user_pass' => Hash::make('password123'),
            'role_id' => 1,
            'user_sts' => 1,
            'user_created' => now(),
            'user_updated' => now(),
        ]);
    }
}
