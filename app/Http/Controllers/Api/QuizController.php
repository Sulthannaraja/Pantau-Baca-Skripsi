<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\SubmitQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\Book;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\QuizSubmission;
use App\Models\ReadingLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class QuizController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureQuizManager($request->user());

        $quizzes = Quiz::query()
            ->with(['book:id,title', 'questions'])
            ->withCount('submissions')
            ->latest('id')
            ->get()
            ->map(fn (Quiz $quiz): array => $this->transformQuiz($quiz, true) + [
                'bookTitle' => $quiz->book?->title,
                'submissionsCount' => $quiz->submissions_count,
            ])
            ->all();

        return response()->json($quizzes);
    }

    public function show(Request $request, Quiz $quiz): JsonResponse
    {
        $user = $request->user();
        $isManager = $user && in_array($user->role, ['GURU', 'ADMIN'], true);

        if (! $isManager) {
            $this->ensureStudentCompleteBook($user, $quiz->book);
            abort_if(! $quiz->active, Response::HTTP_NOT_FOUND, 'Kuis belum aktif.');
        }

        $quiz->load('questions');

        return response()->json($this->transformQuiz($quiz, $isManager));
    }

    public function store(StoreQuizRequest $request): JsonResponse
    {
        $user = $request->user();
        $payload = $request->validated();

        $quiz = Quiz::query()->create([
            'book_id' => $payload['bookId'],
            'created_by' => $user->id,
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'active' => $payload['active'] ?? true,
        ]);

        $quiz->questions()->createMany($this->questionPayload($payload['questions']));

        $quiz->load('questions');

        return response()->json([
            'message' => 'Kuis berhasil dibuat.',
            'quiz' => $this->transformQuiz($quiz, true),
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateQuizRequest $request, Quiz $quiz): JsonResponse
    {
        $this->ensureQuizManager($request->user());

        $payload = $request->validated();

        DB::transaction(function () use ($payload, $quiz): void {
            $quiz->update([
                'book_id' => $payload['bookId'] ?? $quiz->book_id,
                'title' => $payload['title'] ?? $quiz->title,
                'description' => $payload['description'] ?? $quiz->description,
                'active' => array_key_exists('active', $payload) ? $payload['active'] : $quiz->active,
            ]);

            if (array_key_exists('questions', $payload)) {
                $quiz->questions()->delete();
                $quiz->questions()->createMany($this->questionPayload($payload['questions']));
            }
        });

        return response()->json([
            'message' => 'Kuis berhasil diperbarui.',
            'quiz' => $this->transformQuiz($quiz->fresh('questions'), true),
        ]);
    }

    public function destroy(Request $request, Quiz $quiz): JsonResponse
    {
        $this->ensureQuizManager($request->user());

        $quiz->delete();

        return response()->json(['message' => 'Kuis berhasil dihapus.']);
    }

    public function showBookQuiz(Request $request, Book $book): JsonResponse
    {
        $this->ensureStudentCompleteBook($request->user(), $book);

        $quiz = Quiz::query()
            ->where('book_id', $book->id)
            ->where('active', true)
            ->with('questions')
            ->first();

        abort_if(! $quiz, Response::HTTP_NOT_FOUND, 'Kuis tidak ditemukan untuk buku ini.');

        return response()->json($this->transformQuiz($quiz, false));
    }

    public function submit(SubmitQuizRequest $request): JsonResponse
    {
        $user = $request->user();
        $payload = $request->validated();

        $quiz = Quiz::query()
            ->with('questions')
            ->findOrFail($payload['quizId']);

        $this->ensureStudentCompleteBook($user, $quiz->book);

        $recentSubmission = QuizSubmission::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->where('submitted_at', '>=', now()->subSeconds(3))
            ->first();

        if ($recentSubmission) {
            return response()->json([
                'message' => 'Jawaban kuis telah tersimpan sebelumnya.',
                'submissionId' => $recentSubmission->id,
                'score' => $recentSubmission->score,
                'durationSeconds' => $recentSubmission->duration_seconds ?? 0,
                'submittedAt' => $recentSubmission->submitted_at?->toIso8601String(),
            ], Response::HTTP_OK);
        }

        $questionMap = $quiz->questions->keyBy('id');
        $totalPossiblePoints = (int) $quiz->questions->sum('points');
        $earnedPoints = 0;
        $correctCount = 0;
        $wrongCount = 0;
        $answers = [];

        foreach ($payload['answers'] as $answerPayload) {
            $quizQuestionId = $answerPayload['questionId'];
            $response = (string) $answerPayload['response'];

            abort_if(! $questionMap->has($quizQuestionId), Response::HTTP_UNPROCESSABLE_ENTITY, 'Pertanyaan kuis tidak valid.');

            $question = $questionMap->get($quizQuestionId);
            $isCorrect = null;
            $points = 0;

            if ($question->type === 'MCQ') {
                $isCorrect = strcasecmp(trim($question->answer ?? ''), trim($response)) === 0;
                $points = $isCorrect ? (int) $question->points : 0;
                $earnedPoints += $points;
                if ($isCorrect) {
                    $correctCount++;
                } else {
                    $wrongCount++;
                }
            }

            $answers[] = [
                'quiz_question_id' => $quizQuestionId,
                'response' => $response,
                'is_correct' => $isCorrect,
                'points' => $points,
            ];
        }

        $scaledScore = $totalPossiblePoints > 0
            ? (int) round(($earnedPoints / $totalPossiblePoints) * 100)
            : 0;

        $durationSeconds = isset($payload['durationSeconds']) ? (int) $payload['durationSeconds'] : 0;

        $submission = QuizSubmission::query()->create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'score' => $scaledScore,
            'duration_seconds' => $durationSeconds,
            'submitted_at' => now(),
        ]);

        foreach ($answers as $answer) {
            $submission->answers()->create($answer);
        }

        return response()->json([
            'message' => 'Jawaban kuis berhasil disimpan.',
            'submissionId' => $submission->id,
            'score' => $submission->score,
            'correctCount' => $correctCount,
            'wrongCount' => $wrongCount,
            'totalQuestions' => $quiz->questions->count(),
            'durationSeconds' => $submission->duration_seconds,
            'submittedAt' => $submission->submitted_at?->toIso8601String(),
        ], Response::HTTP_CREATED);
    }

    public function mySubmissions(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user || $user->role !== 'SISWA', Response::HTTP_FORBIDDEN, 'Akses siswa diperlukan.');

        $submissions = QuizSubmission::query()
            ->where('user_id', $user->id)
            ->with(['quiz.book:id,title', 'answers'])
            ->latest('submitted_at')
            ->get()
            ->map(fn (QuizSubmission $submission): array => [
                'id' => $submission->id,
                'quizId' => $submission->quiz_id,
                'quizTitle' => $submission->quiz?->title,
                'bookId' => $submission->quiz?->book_id,
                'bookTitle' => $submission->quiz?->book?->title,
                'score' => $submission->score,
                'durationSeconds' => $submission->duration_seconds ?? 0,
                'submittedAt' => $submission->submitted_at?->toIso8601String(),
                'totalQuestions' => $submission->answers->count(),
                'correctCount' => $submission->answers->where('is_correct', true)->count(),
                'wrongCount' => $submission->answers->where('is_correct', false)->count(),
            ])
            ->all();

        return response()->json($submissions);
    }

    public function gradesRecap(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user || ! in_array($user->role, ['GURU', 'ADMIN'], true), Response::HTTP_FORBIDDEN, 'Akses guru atau admin diperlukan.');

        // 1. Get classrooms under this teacher (or all classrooms if admin)
        $classroomsQuery = Classroom::query()->with(['users' => function ($q): void {
            $q->where('role', 'SISWA');
        }]);

        if ($user->role === 'GURU') {
            $classroomsQuery->where('teacher_id', $user->id);
        }

        $classrooms = $classroomsQuery->get();

        // 2. Map students to their classrooms
        $studentClassMap = [];
        $studentMap = [];

        foreach ($classrooms as $classroom) {
            foreach ($classroom->users as $student) {
                if ($student->role === 'SISWA') {
                    $studentMap[$student->id] = $student;
                    $studentClassMap[$student->id][] = [
                        'id' => $classroom->id,
                        'name' => $classroom->name,
                    ];
                }
            }
        }

        $studentIds = array_keys($studentMap);

        // 3. Quiz Submissions for these students
        $allSubmissions = empty($studentIds)
            ? collect()
            : QuizSubmission::query()
                ->whereIn('user_id', $studentIds)
                ->whereNotNull('submitted_at')
                ->with(['quiz.book:id,title', 'student:id,name,email', 'answers'])
                ->orderByDesc('submitted_at')
                ->orderByDesc('id')
                ->get();

        // Calculate attempt count per student and quiz
        $attemptsCountMap = [];
        foreach ($allSubmissions as $sub) {
            $key = $sub->user_id.'_'.$sub->quiz_id;
            $attemptsCountMap[$key] = ($attemptsCountMap[$key] ?? 0) + 1;
        }

        // Keep only the single latest completed submission per student per quiz
        $submissions = $allSubmissions
            ->unique(fn ($sub) => $sub->user_id.'_'.$sub->quiz_id)
            ->values();

        // 4. Statistics Summary
        $totalStudents = count($studentIds);
        $completedStudentIds = $submissions->pluck('user_id')->unique()->all();
        $completedStudentsCount = count($completedStudentIds);
        $pendingStudentsCount = max(0, $totalStudents - $completedStudentsCount);
        $averageScore = $submissions->count() > 0
            ? round((float) $submissions->avg('score'), 1)
            : 0;

        // 5. Build items list
        $items = [];
        $studentsWithSubmissions = [];

        foreach ($submissions as $sub) {
            $studentsWithSubmissions[$sub->user_id] = true;
            $classes = $studentClassMap[$sub->user_id] ?? [];
            $classNames = array_column($classes, 'name');
            $classIds = array_column($classes, 'id');

            $correctCount = $sub->answers->where('is_correct', true)->count();
            $wrongCount = $sub->answers->where('is_correct', false)->count();
            $totalQuestions = $sub->answers->count();
            $attemptCount = $attemptsCountMap[$sub->user_id.'_'.$sub->quiz_id] ?? 1;

            $items[] = [
                'submissionId' => $sub->id,
                'studentId' => $sub->user_id,
                'studentName' => $sub->student?->name ?? 'Siswa',
                'studentEmail' => $sub->student?->email,
                'className' => ! empty($classNames) ? implode(', ', $classNames) : '-',
                'classIds' => $classIds,
                'quizId' => $sub->quiz_id,
                'quizTitle' => $sub->quiz?->title ?? 'Kuis Pemahaman',
                'bookTitle' => $sub->quiz?->book?->title ?? '-',
                'totalQuestions' => $totalQuestions,
                'correctCount' => $correctCount,
                'wrongCount' => $wrongCount,
                'score' => $sub->score,
                'status' => 'COMPLETED',
                'statusLabel' => 'Selesai',
                'attemptCount' => $attemptCount,
                'durationSeconds' => $sub->duration_seconds ?? 0,
                'submittedAt' => $sub->submitted_at?->toIso8601String(),
                'submittedAtFormatted' => $sub->submitted_at ? $sub->submitted_at->format('d M Y, H:i') : '-',
            ];
        }

        // Add students who have not yet submitted any quiz
        foreach ($studentMap as $studentId => $student) {
            if (! isset($studentsWithSubmissions[$studentId])) {
                $classes = $studentClassMap[$studentId] ?? [];
                $classNames = array_column($classes, 'name');
                $classIds = array_column($classes, 'id');

                $items[] = [
                    'submissionId' => null,
                    'studentId' => $studentId,
                    'studentName' => $student->name,
                    'studentEmail' => $student->email,
                    'className' => ! empty($classNames) ? implode(', ', $classNames) : '-',
                    'classIds' => $classIds,
                    'quizId' => null,
                    'quizTitle' => '-',
                    'bookTitle' => '-',
                    'totalQuestions' => null,
                    'correctCount' => null,
                    'wrongCount' => null,
                    'score' => null,
                    'status' => 'NOT_STARTED',
                    'statusLabel' => 'Belum Mengerjakan',
                    'attemptCount' => 0,
                    'durationSeconds' => null,
                    'submittedAt' => null,
                    'submittedAtFormatted' => '-',
                ];
            }
        }

        $availableClasses = $classrooms->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
        ])->values();

        $availableQuizzes = $submissions
            ->pluck('quiz')
            ->filter()
            ->unique('id')
            ->map(fn ($q) => [
                'id' => $q->id,
                'title' => $q->title,
            ])
            ->values();

        return response()->json([
            'summary' => [
                'totalStudents' => $totalStudents,
                'completedStudentsCount' => $completedStudentsCount,
                'pendingStudentsCount' => $pendingStudentsCount,
                'averageScore' => $averageScore,
                'totalSubmissions' => $submissions->count(),
                'totalAttempts' => $allSubmissions->count(),
            ],
            'classrooms' => $availableClasses,
            'quizzes' => $availableQuizzes,
            'items' => $items,
        ]);
    }

    public function showSubmission(Request $request, QuizSubmission $submission): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user, Response::HTTP_UNAUTHORIZED);

        if ($user->role === 'SISWA') {
            abort_if($submission->user_id !== $user->id, Response::HTTP_FORBIDDEN, 'Anda hanya dapat melihat hasil kuis milik sendiri.');
        }

        if ($user->role === 'GURU') {
            $teacherClassroomStudentIds = DB::table('classroom_user')
                ->join('classrooms', 'classrooms.id', '=', 'classroom_user.classroom_id')
                ->where('classrooms.teacher_id', $user->id)
                ->pluck('classroom_user.user_id')
                ->all();

            abort_if(! in_array($submission->user_id, $teacherClassroomStudentIds, true), Response::HTTP_FORBIDDEN, 'Anda tidak berhak melihat hasil kuis siswa ini.');
        }

        $submission->load(['quiz.book:id,title', 'answers.question', 'student:id,name,email']);

        // Historical attempts for this student on this quiz (chronological order)
        $chronological = QuizSubmission::query()
            ->where('quiz_id', $submission->quiz_id)
            ->where('user_id', $submission->user_id)
            ->whereNotNull('submitted_at')
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get(['id', 'score', 'duration_seconds', 'submitted_at']);

        $totalAttempts = $chronological->count();
        $attemptHistory = [];
        $currentAttemptIndex = 1;

        foreach ($chronological as $idx => $att) {
            $attNumber = $idx + 1;
            if ($att->id === $submission->id) {
                $currentAttemptIndex = $attNumber;
            }
            $attemptHistory[] = [
                'id' => $att->id,
                'attemptNumber' => $attNumber,
                'score' => $att->score,
                'durationSeconds' => $att->duration_seconds ?? 0,
                'submittedAt' => $att->submitted_at?->toIso8601String(),
                'submittedAtFormatted' => $att->submitted_at ? $att->submitted_at->format('d M Y, H:i') : '-',
                'isCurrent' => $att->id === $submission->id,
            ];
        }

        // Sort attemptHistory descending so latest attempt is on top
        usort($attemptHistory, fn ($a, $b) => $b['attemptNumber'] <=> $a['attemptNumber']);

        $answers = $submission->answers->map(function ($answer): array {
            $question = $answer->question;

            return [
                'id' => $answer->id,
                'questionId' => $answer->quiz_question_id,
                'type' => $question?->type ?? 'MCQ',
                'question' => $question?->question ?? 'Pertanyaan',
                'options' => $question?->options ?? [],
                'studentResponse' => $answer->response,
                'correctAnswer' => $question?->answer,
                'isCorrect' => $answer->is_correct,
                'points' => $answer->points,
                'maxPoints' => $question?->points ?? 1,
                'explanation' => $question?->explanation,
            ];
        })->all();

        return response()->json([
            'submissionId' => $submission->id,
            'quizId' => $submission->quiz_id,
            'quizTitle' => $submission->quiz?->title,
            'bookId' => $submission->quiz?->book_id,
            'bookTitle' => $submission->quiz?->book?->title,
            'studentId' => $submission->user_id,
            'studentName' => $submission->student?->name ?? 'Siswa',
            'studentEmail' => $submission->student?->email,
            'score' => $submission->score,
            'durationSeconds' => $submission->duration_seconds ?? 0,
            'submittedAt' => $submission->submitted_at?->toIso8601String(),
            'attemptNumber' => $currentAttemptIndex,
            'totalAttempts' => $totalAttempts,
            'attemptHistory' => $attemptHistory,
            'totalQuestions' => count($answers),
            'answeredCount' => collect($answers)->filter(fn ($a) => ! empty(trim((string) $a['studentResponse'])))->count(),
            'correctCount' => collect($answers)->where('isCorrect', true)->count(),
            'wrongCount' => collect($answers)->where('isCorrect', false)->count(),
            'answers' => $answers,
        ]);
    }

    private function transformQuiz(Quiz $quiz, bool $includeSecrets = false): array
    {
        return [
            'quizId' => $quiz->id,
            'bookId' => $quiz->book_id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'active' => (bool) $quiz->active,
            'questions' => $quiz->questions
                ->sortBy('order')
                ->values()
                ->map(function ($question) use ($includeSecrets): array {
                    $data = [
                        'questionId' => $question->id,
                        'type' => $question->type,
                        'question' => $question->question,
                        'options' => $question->options,
                        'points' => (int) $question->points,
                        'order' => (int) ($question->order ?? 0),
                    ];

                    if ($includeSecrets) {
                        $data['answer'] = $question->answer;
                        $data['explanation'] = $question->explanation;
                    }

                    return $data;
                })->all(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $questions
     * @return array<int, array<string, mixed>>
     */
    private function questionPayload(array $questions): array
    {
        return collect($questions)->values()->map(fn (array $question, int $index): array => [
            'type' => $question['type'],
            'question' => $question['question'],
            'options' => $question['options'] ?? null,
            'answer' => $question['answer'] ?? null,
            'points' => (int) ($question['points'] ?? 1),
            'order' => (int) ($question['order'] ?? ($index + 1)),
            'explanation' => $question['explanation'] ?? null,
        ])->all();
    }

    private function ensureQuizManager(?User $user): void
    {
        abort_if(! $user || ! in_array($user->role, ['GURU', 'ADMIN'], true), Response::HTTP_FORBIDDEN, 'Akses guru atau admin diperlukan.');
    }

    private function ensureStudentCompleteBook(?User $user, Book $book): void
    {
        abort_if(! $user || $user->role !== 'SISWA', Response::HTTP_FORBIDDEN, 'Akses siswa diperlukan.');

        $readingLog = ReadingLog::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        abort_if(! $readingLog || $readingLog->progress < 100, Response::HTTP_FORBIDDEN, 'Selesaikan bacaan terlebih dahulu untuk mengakses kuis.');
    }
}
