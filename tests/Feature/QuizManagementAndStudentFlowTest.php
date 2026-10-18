<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Quiz;
use App\Models\ReadingLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuizManagementAndStudentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_quiz_with_mcq_options_and_validation(): void
    {
        $teacher = User::factory()->teacher()->create([
            'api_token' => Str::random(60),
        ]);
        $book = Book::factory()->create();

        // Failed validation when answer is not in options
        $responseFail = $this->withHeaders([
            'Authorization' => "Bearer {$teacher->api_token}",
        ])->postJson('/api/quizzes', [
            'bookId' => $book->id,
            'title' => 'Kuis Bahasa Indonesia',
            'questions' => [
                [
                    'type' => 'MCQ',
                    'question' => 'Apa ide pokok paragraf pertama?',
                    'options' => ['Pilihan A', 'Pilihan B', 'Pilihan C'],
                    'answer' => 'Pilihan Z', // Not in options
                    'points' => 20,
                ],
            ],
        ]);

        $responseFail->assertUnprocessable();
        $responseFail->assertJsonValidationErrors(['questions.0.answer']);

        // Success when answer is one of the options
        $responseSuccess = $this->withHeaders([
            'Authorization' => "Bearer {$teacher->api_token}",
        ])->postJson('/api/quizzes', [
            'bookId' => $book->id,
            'title' => 'Kuis Pemahaman Bab 1',
            'description' => 'Uji pemahaman membaca bab 1',
            'active' => true,
            'questions' => [
                [
                    'type' => 'MCQ',
                    'question' => 'Apa tema utama buku ini?',
                    'options' => ['Pendidikan', 'Petualangan', 'Sejarah', 'Teknologi', 'Lingkungan'],
                    'answer' => 'Teknologi',
                    'points' => 25,
                    'order' => 1,
                    'explanation' => 'Tema teknologi dibahas pada awal paragraf ketiga.',
                ],
                [
                    'type' => 'MCQ',
                    'question' => 'Siapa tokoh protagonis?',
                    'options' => ['Andi', 'Budi', 'Citra', 'Dewi'],
                    'answer' => 'Andi',
                    'points' => 25,
                    'order' => 2,
                    'explanation' => 'Andi adalah tokoh yang memimpin eksplorasi.',
                ],
            ],
        ]);

        $responseSuccess->assertCreated();
        $quizId = $responseSuccess->json('quiz.quizId');
        $this->assertNotNull($quizId);
        $this->assertSame('Kuis Pemahaman Bab 1', $responseSuccess->json('quiz.title'));
        $this->assertCount(2, $responseSuccess->json('quiz.questions'));

        // Teacher can view quiz details with answer and explanation
        $teacherShow = $this->withHeaders([
            'Authorization' => "Bearer {$teacher->api_token}",
        ])->getJson("/api/quizzes/{$quizId}");

        $teacherShow->assertOk();
        $this->assertSame('Teknologi', $teacherShow->json('questions.0.answer'));
        $this->assertSame('Tema teknologi dibahas pada awal paragraf ketiga.', $teacherShow->json('questions.0.explanation'));
    }

    public function test_teacher_can_update_quiz_and_reorder_questions(): void
    {
        $teacher = User::factory()->teacher()->create([
            'api_token' => Str::random(60),
        ]);
        $book = Book::factory()->create();

        $quiz = Quiz::create([
            'book_id' => $book->id,
            'created_by' => $teacher->id,
            'title' => 'Kuis Awal',
            'active' => false,
        ]);

        $quiz->questions()->create([
            'type' => 'MCQ',
            'question' => 'Soal 1',
            'options' => ['A', 'B'],
            'answer' => 'A',
            'points' => 10,
            'order' => 1,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$teacher->api_token}",
        ])->patchJson("/api/quizzes/{$quiz->id}", [
            'title' => 'Kuis Diperbarui',
            'active' => true,
            'questions' => [
                [
                    'type' => 'MCQ',
                    'question' => 'Soal Baru 1',
                    'options' => ['A', 'B', 'C'],
                    'answer' => 'B',
                    'points' => 50,
                    'order' => 1,
                ],
                [
                    'type' => 'MCQ',
                    'question' => 'Soal Baru 2',
                    'options' => ['Benar', 'Salah'],
                    'answer' => 'Benar',
                    'points' => 50,
                    'order' => 2,
                ],
            ],
        ]);

        $response->assertOk();
        $this->assertSame('Kuis Diperbarui', $response->json('quiz.title'));
        $this->assertTrue($response->json('quiz.active'));
        $this->assertCount(2, $response->json('quiz.questions'));
    }

    public function test_student_flow_submit_quiz_view_submissions_and_details(): void
    {
        $teacher = User::factory()->teacher()->create(['api_token' => Str::random(60)]);
        $student = User::factory()->student()->create(['api_token' => Str::random(60)]);
        $otherStudent = User::factory()->student()->create(['api_token' => Str::random(60)]);
        $book = Book::factory()->create();

        // 1. Create Quiz with 2 MCQ questions
        $quiz = Quiz::create([
            'book_id' => $book->id,
            'created_by' => $teacher->id,
            'title' => 'Kuis Evaluasi Digital',
            'active' => true,
        ]);

        $q1 = $quiz->questions()->create([
            'type' => 'MCQ',
            'question' => 'Pertanyaan 1',
            'options' => ['Opsi A', 'Opsi B', 'Opsi C'],
            'answer' => 'Opsi A',
            'points' => 5,
            'order' => 1,
            'explanation' => 'Penjelasan untuk Soal 1',
        ]);

        $q2 = $quiz->questions()->create([
            'type' => 'MCQ',
            'question' => 'Pertanyaan 2',
            'options' => ['Benar', 'Salah'],
            'answer' => 'Benar',
            'points' => 5,
            'order' => 2,
            'explanation' => 'Penjelasan untuk Soal 2',
        ]);

        // Student completed reading 100%
        ReadingLog::factory()->create([
            'user_id' => $student->id,
            'book_id' => $book->id,
            'progress' => 100,
            'duration' => 60,
            'last_page' => 25,
        ]);

        // 2. Student fetches quiz: answer and explanation MUST NOT be visible!
        $studentQuiz = $this->withHeaders([
            'Authorization' => "Bearer {$student->api_token}",
        ])->getJson("/api/books/{$book->id}/quiz");

        $studentQuiz->assertOk();
        $this->assertArrayNotHasKey('answer', $studentQuiz->json('questions.0'));
        $this->assertArrayNotHasKey('explanation', $studentQuiz->json('questions.0'));

        // 3. Student submits 1 correct, 1 wrong (score should be 50 / 100)
        $submitResponse = $this->withHeaders([
            'Authorization' => "Bearer {$student->api_token}",
        ])->postJson('/api/quizzes/submit', [
            'quizId' => $quiz->id,
            'durationSeconds' => 145,
            'answers' => [
                [
                    'questionId' => $q1->id,
                    'response' => 'Opsi A', // correct
                ],
                [
                    'questionId' => $q2->id,
                    'response' => 'Salah', // wrong
                ],
            ],
        ]);

        $submitResponse->assertCreated();
        $this->assertSame(50, $submitResponse->json('score'));
        $this->assertSame(1, $submitResponse->json('correctCount'));
        $this->assertSame(1, $submitResponse->json('wrongCount'));
        $this->assertSame(2, $submitResponse->json('totalQuestions'));
        $this->assertSame(145, $submitResponse->json('durationSeconds'));
        $submissionId = $submitResponse->json('submissionId');

        // 4. Student views their submission history
        $mySubmissions = $this->withHeaders([
            'Authorization' => "Bearer {$student->api_token}",
        ])->getJson('/api/quizzes/my-submissions');

        $mySubmissions->assertOk();
        $this->assertCount(1, $mySubmissions->json());
        $this->assertSame($submissionId, $mySubmissions->json('0.id'));
        $this->assertSame(50, $mySubmissions->json('0.score'));

        // 5. Student views breakdown of their submission
        $detailResponse = $this->withHeaders([
            'Authorization' => "Bearer {$student->api_token}",
        ])->getJson("/api/quizzes/submissions/{$submissionId}");

        $detailResponse->assertOk();
        $this->assertSame(50, $detailResponse->json('score'));
        $this->assertSame('Penjelasan untuk Soal 1', $detailResponse->json('answers.0.explanation'));
        $this->assertSame('Opsi A', $detailResponse->json('answers.0.correctAnswer'));
        $this->assertTrue($detailResponse->json('answers.0.isCorrect'));
        $this->assertFalse($detailResponse->json('answers.1.isCorrect'));

        // 6. Other student cannot access this submission
        $forbiddenResponse = $this->withHeaders([
            'Authorization' => "Bearer {$otherStudent->api_token}",
        ])->getJson("/api/quizzes/submissions/{$submissionId}");

        $forbiddenResponse->assertForbidden();
    }
}
