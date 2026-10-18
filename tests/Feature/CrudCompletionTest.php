<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrudCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_book_and_user(): void
    {
        $admin = User::factory()->admin()->create([
            'api_token' => Str::random(60),
        ]);
        $book = Book::factory()->create([
            'title' => 'Judul Lama',
            'author' => 'Penulis Lama',
        ]);
        $student = User::factory()->student()->create([
            'name' => 'siswa_lama',
            'email' => 'siswa-lama@example.com',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$admin->api_token)
            ->patchJson("/api/books/{$book->id}", [
                'title' => 'Judul Baru',
                'author' => 'Penulis Baru',
                'category' => 'Novel',
            ])
            ->assertOk()
            ->assertJsonFragment([
                'title' => 'Judul Baru',
                'author' => 'Penulis Baru',
                'category' => 'Novel',
            ]);

        $this->withHeader('Authorization', 'Bearer '.$admin->api_token)
            ->patchJson("/api/users/{$student->id}", [
                'name' => 'siswa_baru',
                'email' => 'siswa-baru@example.com',
                'role' => 'GURU',
            ])
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'siswa_baru',
                'email' => 'siswa-baru@example.com',
                'role' => 'GURU',
            ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'Judul Baru',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'role' => 'GURU',
        ]);
    }

    public function test_teacher_can_manage_quiz_lifecycle(): void
    {
        $teacher = User::factory()->teacher()->create([
            'api_token' => Str::random(60),
        ]);
        $book = Book::factory()->create();
        $quiz = Quiz::query()->create([
            'book_id' => $book->id,
            'created_by' => $teacher->id,
            'title' => 'Kuis Lama',
            'description' => null,
            'active' => true,
        ]);
        $quiz->questions()->create([
            'type' => 'MCQ',
            'question' => 'Pertanyaan lama?',
            'options' => ['A', 'B'],
            'answer' => 'A',
            'points' => 1,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$teacher->api_token)
            ->getJson('/api/quizzes')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Kuis Lama']);

        $this->withHeader('Authorization', 'Bearer '.$teacher->api_token)
            ->patchJson("/api/quizzes/{$quiz->id}", [
                'title' => 'Kuis Baru',
                'active' => false,
                'questions' => [
                    [
                        'type' => 'ESSAY',
                        'question' => 'Jelaskan pesan utama buku.',
                        'points' => 3,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('quiz.title', 'Kuis Baru')
            ->assertJsonPath('quiz.active', false);

        $this->assertDatabaseHas('quiz_questions', [
            'quiz_id' => $quiz->id,
            'type' => 'ESSAY',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$teacher->api_token)
            ->deleteJson("/api/quizzes/{$quiz->id}")
            ->assertOk();

        $this->assertDatabaseMissing('quizzes', [
            'id' => $quiz->id,
        ]);
    }
}
