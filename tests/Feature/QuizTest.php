<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ReadingLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_quiz_and_student_can_access_after_completion(): void
    {
        $teacher = User::factory()->teacher()->create([
            'api_token' => Str::random(60),
        ]);

        $book = Book::factory()->create();

        $quizResponse = $this->withHeaders([
            'Authorization' => "Bearer {$teacher->api_token}",
        ])->postJson('/api/quizzes', [
            'bookId' => $book->id,
            'title' => 'Kuis Akhir Bacaan',
            'description' => 'Uji pemahaman siswa setelah membaca.',
            'questions' => [
                [
                    'type' => 'MCQ',
                    'question' => 'Apa warna langit?',
                    'options' => ['Biru', 'Merah'],
                    'answer' => 'Biru',
                    'points' => 2,
                ],
                [
                    'type' => 'ESSAY',
                    'question' => 'Sebutkan satu contoh hewan.',
                    'answer' => 'Kucing',
                    'points' => 3,
                ],
            ],
        ]);

        $quizResponse->assertCreated();
        $this->assertSame('Kuis Akhir Bacaan', $quizResponse->json('quiz.title'));

        $student = User::factory()->student()->create([
            'api_token' => Str::random(60),
        ]);

        ReadingLog::factory()->create([
            'user_id' => $student->id,
            'book_id' => $book->id,
            'progress' => 100,
            'duration' => 45,
            'last_page' => 20,
            'started_at' => now()->subMinutes(45),
        ]);

        $studentQuizResponse = $this->withHeaders([
            'Authorization' => "Bearer {$student->api_token}",
        ])->getJson("/api/books/{$book->id}/quiz");

        $studentQuizResponse->assertOk();
        $this->assertSame($quizResponse->json('quiz.quizId'), $studentQuizResponse->json('quizId'));
    }

    public function test_admin_can_create_quiz_from_teacher_dashboard_flow(): void
    {
        $admin = User::factory()->admin()->create([
            'api_token' => Str::random(60),
        ]);
        $book = Book::factory()->create();

        $this->withHeaders([
            'Authorization' => "Bearer {$admin->api_token}",
        ])->postJson('/api/quizzes', [
            'bookId' => $book->id,
            'title' => 'Kuis Admin',
            'questions' => [
                [
                    'type' => 'MCQ',
                    'question' => 'Apa ibu kota Indonesia?',
                    'options' => ['Jakarta', 'Bandung'],
                    'answer' => 'Jakarta',
                    'points' => 1,
                ],
            ],
        ])->assertCreated()
            ->assertJsonPath('quiz.title', 'Kuis Admin');
    }

    public function test_student_cannot_access_quiz_before_completing_reading(): void
    {
        $student = User::factory()->student()->create([
            'api_token' => Str::random(60),
        ]);
        $book = Book::factory()->create();

        ReadingLog::factory()->create([
            'user_id' => $student->id,
            'book_id' => $book->id,
            'progress' => 80,
            'duration' => 25,
            'last_page' => 12,
        ]);

        $this->withHeaders([
            'Authorization' => "Bearer {$student->api_token}",
        ])->getJson("/api/books/{$book->id}/quiz")
            ->assertForbidden();
    }

    public function test_reader_page_route_is_accessible(): void
    {
        $this->get('/reader')->assertOk();
    }
}
