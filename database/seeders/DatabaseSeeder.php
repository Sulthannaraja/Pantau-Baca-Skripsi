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
        // Admin Accounts
        User::query()->updateOrCreate(
            ['email' => 'admin@pantaubaca.com'],
            [
                'name' => 'admin',
                'password' => Hash::make('admin123'),
                'role' => 'ADMIN',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'sulthantegarnaraja@admin.local'],
            [
                'name' => 'sulthantegarnaraja',
                'password' => Hash::make('admin123'),
                'role' => 'ADMIN',
            ]
        );

        // Guru Demo Account
        User::query()->updateOrCreate(
            ['email' => 'guru@pantaubaca.com'],
            [
                'name' => 'Budi Guru',
                'password' => Hash::make('guru123'),
                'role' => 'GURU',
            ]
        );

        // Siswa Demo Account
        User::query()->updateOrCreate(
            ['email' => 'siswa@pantaubaca.com'],
            [
                'name' => 'Ahmad Siswa',
                'password' => Hash::make('siswa123'),
                'role' => 'SISWA',
            ]
        );

        $this->call([
            BookResumeSeeder::class,
            LiteracySurveySeeder::class,
        ]);
    }
}
