<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\QuizSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuizGradesRecapTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_grades_recap(): void
    {
        $response = $this->getJson('/api/quizzes/grades-recap');
        $response->assertUnauthorized();
    }

    public function test_student_cannot_access_grades_recap(): void
    {
        $student = User::factory()->student()->create([
            'api_token' => Str::random(60),
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$student->api_token}",
        ])->getJson('/api/quizzes/grades-recap');

        $response->assertForbidden();
    }

    public function test_teacher_can_view_recap_only_for_students_in_their_classrooms(): void
    {
        // Teacher A and Classroom A
        $teacherA = User::factory()->teacher()->create([
            'api_token' => Str::random(60),
        ]);
        $classroomA = Classroom::create([
            'name' => 'Kelas 10-A',
            'teacher_id' => $teacherA->id,
            'code' => 'KLS10A',
        ]);

        // Student A belongs to Classroom A
        $studentA = User::factory()->student()->create(['name' => 'Ahmad Siswa']);
        $classroomA->users()->attach($studentA->id);

        // Student A2 belongs to Classroom A but hasn't submitted any quiz
        $studentA2 = User::factory()->student()->create(['name' => 'Dewi Siswa']);
        $classroomA->users()->attach($studentA2->id);

        // Teacher B and Classroom B with Student B
        $teacherB = User::factory()->teacher()->create([
            'api_token' => Str::random(60),
        ]);
        $classroomB = Classroom::create([
            'name' => 'Kelas 10-B',
            'teacher_id' => $teacherB->id,
            'code' => 'KLS10B',
        ]);
        $studentB = User::factory()->student()->create(['name' => 'Budi Luar Kelas']);
        $classroomB->users()->attach($studentB->id);

        // Quiz and submissions
        $book = Book::factory()->create(['title' => 'Buku Literasi']);
        $quiz = Quiz::create([
            'book_id' => $book->id,
            'created_by' => $teacherA->id,
            'title' => 'Kuis Bab 1',
            'active' => true,
        ]);
        $q1 = $quiz->questions()->create([
            'type' => 'MCQ',
            'question' => 'Soal 1',
            'options' => ['A', 'B'],
            'answer' => 'A',
            'points' => 10,
        ]);

        // Student A submits quiz with score 100
        $subA = QuizSubmission::create([
            'quiz_id' => $quiz->id,
            'user_id' => $studentA->id,
            'score' => 100,
            'duration_seconds' => 120,
            'submitted_at' => now(),
        ]);
        $subA->answers()->create([
            'quiz_question_id' => $q1->id,
            'response' => 'A',
            'is_correct' => true,
            'points' => 10,
        ]);

        // Student B submits quiz with score 50
        $subB = QuizSubmission::create([
            'quiz_id' => $quiz->id,
            'user_id' => $studentB->id,
            'score' => 50,
            'duration_seconds' => 90,
            'submitted_at' => now(),
        ]);
        $subB->answers()->create([
            'quiz_question_id' => $q1->id,
            'response' => 'B',
            'is_correct' => false,
            'points' => 0,
        ]);

        // Teacher A fetches grades recap
        $responseA = $this->withHeaders([
            'Authorization' => "Bearer {$teacherA->api_token}",
        ])->getJson('/api/quizzes/grades-recap');

        $responseA->assertOk();

        // Summary assertions for Teacher A
        $responseA->assertJsonPath('summary.totalStudents', 2); // Student A and Student A2
        $responseA->assertJsonPath('summary.completedStudentsCount', 1); // Student A
        $responseA->assertJsonPath('summary.pendingStudentsCount', 1); // Student A2
        $responseA->assertJsonPath('summary.averageScore', 100);

        // Items assertion: Teacher A must see Student A (completed) and Student A2 (not started)
        $items = $responseA->json('items');
        $this->assertCount(2, $items);

        $studentNames = collect($items)->pluck('studentName')->all();
        $this->assertContains('Ahmad Siswa', $studentNames);
        $this->assertContains('Dewi Siswa', $studentNames);
        $this->assertNotContains('Budi Luar Kelas', $studentNames); // Student B must NOT be visible to Teacher A!

        // Find Student A item
        $itemA = collect($items)->firstWhere('studentName', 'Ahmad Siswa');
        $this->assertSame('COMPLETED', $itemA['status']);
        $this->assertSame(100, $itemA['score']);
        $this->assertSame(1, $itemA['correctCount']);
        $this->assertSame('Kelas 10-A', $itemA['className']);

        // Find Student A2 item (unsubmitted)
        $itemA2 = collect($items)->firstWhere('studentName', 'Dewi Siswa');
        $this->assertSame('NOT_STARTED', $itemA2['status']);
        $this->assertNull($itemA2['score']);
        $this->assertSame('Belum Mengerjakan', $itemA2['statusLabel']);

        // Teacher A can inspect Student A's submission detail
        $detailResponse = $this->withHeaders([
            'Authorization' => "Bearer {$teacherA->api_token}",
        ])->getJson("/api/quizzes/submissions/{$subA->id}");

        $detailResponse->assertOk();
        $this->assertSame('Ahmad Siswa', $detailResponse->json('studentName'));
        $this->assertSame(100, $detailResponse->json('score'));

        // Teacher A CANNOT inspect Student B's submission detail (forbidden)
        $forbiddenResponse = $this->withHeaders([
            'Authorization' => "Bearer {$teacherA->api_token}",
        ])->getJson("/api/quizzes/submissions/{$subB->id}");

        $forbiddenResponse->assertForbidden();
    }

    public function test_recap_only_shows_latest_attempt_per_student_per_quiz_and_preserves_history(): void
    {
        $teacher = User::factory()->teacher()->create([
            'api_token' => Str::random(60),
        ]);
        $classroom = Classroom::create([
            'name' => 'Kelas 10-A',
            'teacher_id' => $teacher->id,
            'code' => 'KLS10A',
        ]);
        $student = User::factory()->student()->create(['name' => 'Fajar Siswa']);
        $classroom->users()->attach($student->id);

        $book = Book::factory()->create(['title' => 'Buku Pemrograman']);
        $quiz = Quiz::create([
            'book_id' => $book->id,
            'created_by' => $teacher->id,
            'title' => 'Kuis Logika',
            'active' => true,
        ]);
        $question = $quiz->questions()->create([
            'type' => 'MCQ',
            'question' => '1 + 1 = ?',
            'options' => ['2', '3'],
            'answer' => '2',
            'points' => 10,
        ]);

        // Attempt 1: Score 40 (earlier)
        $firstAttempt = QuizSubmission::create([
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 40,
            'duration_seconds' => 180,
            'submitted_at' => now()->subHours(2),
        ]);
        $firstAttempt->answers()->create([
            'quiz_question_id' => $question->id,
            'response' => '3',
            'is_correct' => false,
            'points' => 0,
        ]);

        // Attempt 2: Score 90 (latest)
        $secondAttempt = QuizSubmission::create([
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 90,
            'duration_seconds' => 100,
            'submitted_at' => now(),
        ]);
        $secondAttempt->answers()->create([
            'quiz_question_id' => $question->id,
            'response' => '2',
            'is_correct' => true,
            'points' => 10,
        ]);

        // Check recap
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$teacher->api_token}",
        ])->getJson('/api/quizzes/grades-recap');

        $response->assertOk();

        // Must show only 1 row in recap items for this student and quiz
        $items = $response->json('items');
        $this->assertCount(1, $items);

        $recapItem = $items[0];
        $this->assertSame($student->id, $recapItem['studentId']);
        $this->assertSame($secondAttempt->id, $recapItem['submissionId']);
        $this->assertSame(90, $recapItem['score'], 'Nilai yang ditampilkan harus nilai dari percobaan terakhir (90), bukan percobaan pertama (40)');
        $this->assertSame(2, $recapItem['attemptCount']);

        // Both attempts must still exist in the database (riwayat percobaan tetap disimpan)
        $this->assertDatabaseCount('quiz_submissions', 2);
        $this->assertDatabaseHas('quiz_submissions', ['id' => $firstAttempt->id, 'score' => 40]);
        $this->assertDatabaseHas('quiz_submissions', ['id' => $secondAttempt->id, 'score' => 90]);

        // Summary must reflect the latest attempt
        $response->assertJsonPath('summary.totalSubmissions', 1);
        $response->assertJsonPath('summary.totalAttempts', 2);
        $response->assertJsonPath('summary.averageScore', 90);

        // Detail endpoint returns attempt history
        $detailResponse = $this->withHeaders([
            'Authorization' => "Bearer {$teacher->api_token}",
        ])->getJson("/api/quizzes/submissions/{$secondAttempt->id}");

        $detailResponse->assertOk();
        $detailResponse->assertJsonPath('totalAttempts', 2);
        $detailResponse->assertJsonPath('attemptNumber', 2);
        $detailResponse->assertJsonCount(2, 'attemptHistory');
    }

    public function test_recap_groups_separately_for_different_quizzes_or_books(): void
    {
        $teacher = User::factory()->teacher()->create([
            'api_token' => Str::random(60),
        ]);
        $classroom = Classroom::create([
            'name' => 'Kelas 10-A',
            'teacher_id' => $teacher->id,
            'code' => 'KLS10A',
        ]);
        $student = User::factory()->student()->create(['name' => 'Gita Siswa']);
        $classroom->users()->attach($student->id);

        $book1 = Book::factory()->create(['title' => 'Buku Sains']);
        $quiz1 = Quiz::create([
            'book_id' => $book1->id,
            'created_by' => $teacher->id,
            'title' => 'Kuis Sains',
            'active' => true,
        ]);

        $book2 = Book::factory()->create(['title' => 'Buku Sejarah']);
        $quiz2 = Quiz::create([
            'book_id' => $book2->id,
            'created_by' => $teacher->id,
            'title' => 'Kuis Sejarah',
            'active' => true,
        ]);

        // Student submits Quiz 1: Attempt 1 = 60, Attempt 2 = 80
        QuizSubmission::create([
            'quiz_id' => $quiz1->id,
            'user_id' => $student->id,
            'score' => 60,
            'submitted_at' => now()->subDay(),
        ]);
        $quiz1Latest = QuizSubmission::create([
            'quiz_id' => $quiz1->id,
            'user_id' => $student->id,
            'score' => 80,
            'submitted_at' => now()->subHours(5),
        ]);

        // Student submits Quiz 2: Attempt 1 = 100
        $quiz2Latest = QuizSubmission::create([
            'quiz_id' => $quiz2->id,
            'user_id' => $student->id,
            'score' => 100,
            'submitted_at' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$teacher->api_token}",
        ])->getJson('/api/quizzes/grades-recap');

        $response->assertOk();

        // Exactly 2 rows: one for Quiz Sains (score 80), one for Quiz Sejarah (score 100)
        $items = $response->json('items');
        $this->assertCount(2, $items);

        $sainsItem = collect($items)->firstWhere('quizId', $quiz1->id);
        $this->assertNotNull($sainsItem);
        $this->assertSame(80, $sainsItem['score']);
        $this->assertSame($quiz1Latest->id, $sainsItem['submissionId']);
        $this->assertSame(2, $sainsItem['attemptCount']);

        $sejarahItem = collect($items)->firstWhere('quizId', $quiz2->id);
        $this->assertNotNull($sejarahItem);
        $this->assertSame(100, $sejarahItem['score']);
        $this->assertSame($quiz2Latest->id, $sejarahItem['submissionId']);
        $this->assertSame(1, $sejarahItem['attemptCount']);
    }
}
