<?php

namespace Database\Seeders;

use App\Models\LiteracySurvey;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LiteracySurveySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = [
            [
                'name' => 'Aditya Pratama',
                'email' => 'aditya.sman3@pantaubaca.com',
                'responses' => [3, 4, 3, 4, 3, 4, 3, 4, 3, 4], // Total: 35 -> TINGGI
            ],
            [
                'name' => 'Dinda Rahmawati',
                'email' => 'dinda.sman3@pantaubaca.com',
                'responses' => [2, 2, 2, 3, 2, 3, 2, 2, 3, 2], // Total: 23 -> RENDAH
            ],
            [
                'name' => 'Fajar Nugraha',
                'email' => 'fajar.sman3@pantaubaca.com',
                'responses' => [4, 4, 3, 4, 4, 3, 4, 3, 4, 4], // Total: 37 -> TINGGI
            ],
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'siti.sman3@pantaubaca.com',
                'responses' => [2, 3, 2, 2, 2, 2, 2, 3, 2, 3], // Total: 23 -> RENDAH
            ],
            [
                'name' => 'Bagas Wicaksono',
                'email' => 'bagas.sman3@pantaubaca.com',
                'responses' => [3, 3, 3, 3, 3, 3, 3, 3, 3, 3], // Total: 30 -> TINGGI
            ],
            [
                'name' => 'Nabila Putri',
                'email' => 'nabila.sman3@pantaubaca.com',
                'responses' => [2, 2, 1, 2, 2, 3, 2, 2, 3, 2], // Total: 21 -> RENDAH
            ],
        ];

        foreach ($students as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'SISWA',
                ]
            );

            $responses = $data['responses'];
            $totalScore = array_sum($responses);
            $level = $totalScore >= 28 ? 'TINGGI' : 'RENDAH';

            LiteracySurvey::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'responses' => $responses,
                    'total_score' => $totalScore,
                    'level' => $level,
                ]
            );
        }

        // Also ensure default demo student 'Ahmad Siswa' has an initial survey result (RENDAH so they can see transition or start at Dasar)
        $defaultSiswa = User::query()->where('email', 'siswa@pantaubaca.com')->first();
        if ($defaultSiswa) {
            $responses = [2, 3, 2, 2, 2, 3, 2, 2, 3, 3]; // 24 -> RENDAH
            $total = array_sum($responses);
            LiteracySurvey::query()->updateOrCreate(
                ['user_id' => $defaultSiswa->id],
                [
                    'responses' => $responses,
                    'total_score' => $total,
                    'level' => 'RENDAH',
                ]
            );
        }
    }
}
