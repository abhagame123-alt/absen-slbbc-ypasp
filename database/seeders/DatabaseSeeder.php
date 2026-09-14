<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Buat akun admin utama untuk production
        User::updateOrCreate(
            ['email' => 'abhaadmin234@gmail.com'],
            [
                'name' => 'Admin Absen',
                'password' => Hash::make('password123'), // Ganti password sesuai keinginanmu
                'email_verified_at' => now(),
            ]
        );
    }
}