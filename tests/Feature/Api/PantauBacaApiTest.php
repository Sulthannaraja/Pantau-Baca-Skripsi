<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\ReadingLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PantauBacaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login(): void
    {
        $this->postJson('/api/register', [
            'name' => 'budi123',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'role' => 'SISWA',
        ])->assertCreated();

        $this->postJson('/api/login', [
            'email' => 'budi@example.com',
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonStructure(['token', 'role', 'userId', 'name']);
    }

    public function test_admin_can_upload_and_list_books(): void
    {
        Storage::fake('local');

        $admin = User::factory()->admin()->create([
            'api_token' => 'admin-token',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$admin->api_token)
            ->post('/api/books/upload', [
                'title' => 'Literasi Digital Dasar',
                'author' => 'Tim Edukasi',
                'category' => 'Buku Pembelajaran',
                'literacy_level' => 'RENDAH',
                'cover' => 'https://example.com/cover.jpg',
                'file' => UploadedFile::fake()->create('book.pdf', 100, 'application/pdf'),
            ]);

        $response->assertCreated()
            ->assertJsonFragment(['title' => 'Literasi Digital Dasar', 'literacy_level' => 'RENDAH']);

        $this->getJson('/api/books')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_teacher_can_upload_and_list_books(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->create([
            'role' => 'GURU',
            'api_token' => 'teacher-token',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$teacher->api_token)
            ->post('/api/books/upload', [
                'title' => 'Buku Guru Inspiratif',
                'author' => 'Guru Penggerak SMAN 3',
                'year' => 2024,
                'category' => 'Buku Pembelajaran',
                'literacy_level' => 'TINGGI',
                'cover' => 'https://example.com/cover-guru.jpg',
                'file' => UploadedFile::fake()->create('teacher_book.pdf', 100, 'application/pdf'),
            ]);

        $response->assertCreated()
            ->assertJsonFragment(['title' => 'Buku Guru Inspiratif', 'year' => 2024, 'literacy_level' => 'TINGGI']);
    }

    public function test_student_can_start_and_update_reading_progress(): void
    {
        $student = User::factory()->student()->create([
            'api_token' => 'student-token',
        ]);
        $book = Book::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$student->api_token)
            ->postJson('/api/reading/start', [
                'bookId' => $book->id,
                'currentPage' => 3,
            ])->assertOk()
            ->assertJsonFragment(['lastPage' => 3]);

        $this->withHeader('Authorization', 'Bearer '.$student->api_token)
            ->postJson('/api/reading/progress', [
                'bookId' => $book->id,
                'duration' => 45,
                'progress' => 72,
                'lastPage' => 16,
            ])->assertOk()
            ->assertJsonFragment(['duration' => 45, 'progress' => 72, 'lastPage' => 16]);

        $this->assertDatabaseHas('reading_logs', [
            'user_id' => $student->id,
            'book_id' => $book->id,
            'duration' => 45,
            'progress' => 72,
            'last_page' => 16,
        ]);
    }

    public function test_teacher_can_view_reading_report(): void
    {
        $teacher = User::factory()->teacher()->create([
            'api_token' => 'teacher-token',
        ]);
        $student = User::factory()->student()->create();
        $book = Book::factory()->create([
            'title' => 'Belajar Internet Aman',
        ]);

        ReadingLog::factory()->create([
            'user_id' => $student->id,
            'book_id' => $book->id,
            'duration' => 20,
            'progress' => 44,
            'last_page' => 18,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$teacher->api_token)
            ->getJson('/api/reading/report')
            ->assertOk()
            ->assertJsonFragment([
                'userName' => $student->name,
                'bookTitle' => 'Belajar Internet Aman',
                'duration' => 20,
                'progress' => 44,
                'lastPage' => 18,
            ]);
    }

    public function test_student_can_create_resume_and_teacher_cannot(): void
    {
        $student = User::factory()->student()->create([
            'api_token' => 'student-token',
        ]);
        $teacher = User::factory()->teacher()->create([
            'api_token' => 'teacher-token',
        ]);
        $book = Book::factory()->create();

        // Teacher cannot create resume
        $this->withHeader('Authorization', 'Bearer '.$teacher->api_token)
            ->postJson("/api/books/{$book->id}/resumes", [
                'content' => 'Resume guru yang seharusnya ditolak.',
            ])->assertForbidden();

        // Student can create resume
        $response = $this->withHeader('Authorization', 'Bearer '.$student->api_token)
            ->postJson("/api/books/{$book->id}/resumes", [
                'content' => 'Ini adalah rangkuman buku oleh siswa.',
            ]);

        $response->assertOk()
            ->assertJsonFragment([
                'content' => 'Ini adalah rangkuman buku oleh siswa.',
            ]);

        $this->assertDatabaseHas('book_resumes', [
            'book_id' => $book->id,
            'user_id' => $student->id,
            'content' => 'Ini adalah rangkuman buku oleh siswa.',
        ]);

        $this->getJson("/api/books/{$book->id}/resumes")
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_teacher_can_grade_student_resume(): void
    {
        $student = User::factory()->student()->create([
            'api_token' => 'student-token',
        ]);
        $teacher = User::factory()->teacher()->create([
            'api_token' => 'teacher-token',
        ]);
        $book = Book::factory()->create();

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$student->api_token)
            ->postJson("/api/books/{$book->id}/resumes", [
                'content' => 'Rangkuman siswa untuk dinilai.',
            ])->assertOk();

        $resumeId = $createResponse->json('resume.id');

        // Student cannot grade
        $this->withHeader('Authorization', 'Bearer '.$student->api_token)
            ->postJson("/api/resumes/{$resumeId}/grade", [
                'score' => 95,
            ])->assertForbidden();

        // Teacher can grade
        $this->withHeader('Authorization', 'Bearer '.$teacher->api_token)
            ->postJson("/api/resumes/{$resumeId}/grade", [
                'score' => 88,
                'teacher_feedback' => 'Resume sangat bagus dan mendalam.',
            ])->assertOk()
            ->assertJsonFragment([
                'score' => 88,
                'teacher_feedback' => 'Resume sangat bagus dan mendalam.',
            ]);

        $this->assertDatabaseHas('book_resumes', [
            'id' => $resumeId,
            'score' => 88,
            'teacher_feedback' => 'Resume sangat bagus dan mendalam.',
            'graded_by' => $teacher->id,
        ]);
    }

    public function test_books_can_be_filtered_by_literacy_level(): void
    {
        Book::factory()->create([
            'title' => 'Buku Dasar Pemula',
            'literacy_level' => 'RENDAH',
        ]);
        Book::factory()->create([
            'title' => 'Buku Analisis Kritis',
            'literacy_level' => 'TINGGI',
        ]);

        $responseLow = $this->getJson('/api/books?literacy_level=RENDAH');
        $responseLow->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Buku Dasar Pemula', 'literacy_level' => 'RENDAH']);

        $responseHigh = $this->getJson('/api/books?literacy_level=TINGGI');
        $responseHigh->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Buku Analisis Kritis', 'literacy_level' => 'TINGGI']);
    }

    public function test_student_gets_auto_recommended_books_based_on_survey(): void
    {
        $student = User::factory()->student()->create([
            'api_token' => 'student-survey-token',
        ]);

        // Student took survey and got RENDAH
        $student->surveys()->create([
            'responses' => [1, 2, 1, 2, 1],
            'total_score' => 20,
            'level' => 'RENDAH',
        ]);

        Book::factory()->create([
            'title' => 'Buku Langkah Dasar',
            'literacy_level' => 'RENDAH',
        ]);
        Book::factory()->create([
            'title' => 'Buku Kajian Mendalam',
            'literacy_level' => 'TINGGI',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$student->api_token)
            ->getJson('/api/books?auto_recommend=1');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Buku Langkah Dasar', 'literacy_level' => 'RENDAH']);
    }

    public function test_can_get_book_cover_or_fallback_svg(): void
    {
        $book = Book::factory()->create([
            'title' => 'Koleksi Cerpen Nusantara',
            'author' => 'Sastrawan Muda',
            'cover' => null,
        ]);

        $response = $this->get('/api/books/'.$book->id.'/cover');
        $response->assertOk();
        $this->assertStringContainsString('image/svg+xml', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Koleksi Cerpen Nusantara', $response->getContent());
    }

    public function test_can_save_book_cover_base64(): void
    {
        $book = Book::factory()->create([
            'cover' => null,
        ]);

        // 1x1 transparent/white test png image in base64
        $fakeBase64 = 'data:image/jpeg;base64,'.base64_encode(str_repeat('A', 200));

        $response = $this->postJson('/api/books/'.$book->id.'/cover', [
            'cover_data' => $fakeBase64,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['cover' => '/covers/cover_'.$book->id.'.jpg']);

        $this->assertFileExists(public_path('covers/cover_'.$book->id.'.jpg'));

        // Cleanup test generated file
        if (file_exists(public_path('covers/cover_'.$book->id.'.jpg'))) {
            unlink(public_path('covers/cover_'.$book->id.'.jpg'));
        }
    }
}
