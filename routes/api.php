<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\LiteracySurveyController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\ReadingController;
use App\Http\Controllers\Api\ResumeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\ClassroomController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/books', [BookController::class, 'index']);
Route::get('/books/{book}', [BookController::class, 'show']);
Route::get('/books/{book}/cover', [BookController::class, 'cover']);
Route::post('/books/{book}/cover', [BookController::class, 'saveCover']);
Route::get('/books/{book}/resumes', [ResumeController::class, 'index']);

Route::middleware('api.token')->group(function (): void {
    Route::post('/books/upload', [BookController::class, 'upload']);
    Route::patch('/books/{book}', [BookController::class, 'update']);
    Route::delete('/books/{book}', [BookController::class, 'destroy']);
    Route::get('/books/{book}/pages/meta', [BookController::class, 'pageMeta']);
    Route::get('/books/{book}/pages/{page}', [BookController::class, 'pageImage']);
    Route::get('/books/{book}/stream', [BookController::class, 'stream']);

    Route::get('/users', [UserController::class, 'index']);
    Route::patch('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);

    Route::post('/reading/start', [ReadingController::class, 'start']);
    Route::post('/reading/progress', [ReadingController::class, 'progress']);
    Route::get('/reading/report', [ReadingController::class, 'report']);

    Route::post('/quizzes', [QuizController::class, 'store']);
    Route::get('/quizzes', [QuizController::class, 'index']);
    Route::get('/quizzes/grades-recap', [QuizController::class, 'gradesRecap']);
    Route::get('/quizzes/my-submissions', [QuizController::class, 'mySubmissions']);
    Route::get('/quizzes/submissions/{submission}', [QuizController::class, 'showSubmission']);
    Route::get('/quizzes/{quiz}', [QuizController::class, 'show']);
    Route::patch('/quizzes/{quiz}', [QuizController::class, 'update']);
    Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy']);
    Route::get('/books/{book}/quiz', [QuizController::class, 'showBookQuiz']);
    Route::post('/quizzes/submit', [QuizController::class, 'submit']);

    Route::get('/resumes', [ResumeController::class, 'allResumes']);
    Route::post('/books/{book}/resumes', [ResumeController::class, 'store']);
    Route::match(['post', 'patch'], '/resumes/{resume}/grade', [ResumeController::class, 'grade']);
    Route::delete('/resumes/{resume}', [ResumeController::class, 'destroy']);

    // Angket Literasi Digital (Likert 1-4)
    Route::post('/literacy-survey', [LiteracySurveyController::class, 'submit']);
    Route::get('/literacy-survey/my-result', [LiteracySurveyController::class, 'myResult']);
    Route::get('/literacy-surveys', [LiteracySurveyController::class, 'index']);

    // Classrooms
    Route::get('/classrooms', [ClassroomController::class, 'index']);
    Route::post('/classrooms', [ClassroomController::class, 'store']);
    Route::post('/classrooms/join', [ClassroomController::class, 'join']);
    Route::delete('/classrooms/{classroom}', [ClassroomController::class, 'destroy']);
});
