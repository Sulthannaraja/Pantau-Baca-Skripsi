<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookResume;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResumeController extends Controller
{
    /**
     * Get all resumes for a specific book.
     */
    public function index(Request $request, Book $book): JsonResponse
    {
        $query = $book->resumes()->with(['user:id,name,email', 'grader:id,name'])->latest('updated_at');

        if ($request->filled('category')) {
            $query->where('category', strtoupper($request->query('category')));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        return response()->json($query->get());
    }

    /**
     * Get all resumes across books (with role filtering).
     */
    public function allResumes(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user, Response::HTTP_UNAUTHORIZED, 'Token tidak valid.');

        $query = BookResume::query()
            ->with(['user:id,name,email', 'book:id,title,author,cover,category', 'grader:id,name'])
            ->latest('updated_at');

        if ($user->role === 'SISWA') {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            if ($request->query('status') === 'graded') {
                $query->whereNotNull('score');
            } elseif ($request->query('status') === 'pending') {
                $query->whereNull('score');
            }
        }

        if ($request->filled('book_id')) {
            $query->where('book_id', $request->query('book_id'));
        }

        return response()->json($query->get());
    }

    /**
     * Save or update a resume for a book. Only students are allowed.
     */
    public function store(Request $request, Book $book): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user, Response::HTTP_UNAUTHORIZED, 'Token tidak valid.');
        abort_if($user->role !== 'SISWA', Response::HTTP_FORBIDDEN, 'Hanya siswa yang dapat membuat resume.');

        $validated = $request->validate([
            'category' => ['nullable', 'in:DASAR,LANJUTAN'],
            'tujuan_penyajian' => ['nullable', 'string'],
            'kedalaman_materi' => ['nullable', 'string'],
            'bahasa' => ['nullable', 'string'],
            'penyajian_materi' => ['nullable', 'string'],
            'contoh' => ['nullable', 'string'],
            'content' => ['required', 'string'],
        ]);

        $category = $validated['category'] ?? 'DASAR';

        $resume = BookResume::query()->updateOrCreate(
            [
                'book_id' => $book->id,
                'user_id' => $user->id,
            ],
            [
                'category' => $category,
                'tujuan_penyajian' => $validated['tujuan_penyajian'] ?? null,
                'kedalaman_materi' => $validated['kedalaman_materi'] ?? null,
                'bahasa' => $validated['bahasa'] ?? null,
                'penyajian_materi' => $validated['penyajian_materi'] ?? null,
                'contoh' => $validated['contoh'] ?? null,
                'content' => $validated['content'],
            ]
        );

        return response()->json([
            'message' => 'Resume berhasil dikirim ke guru.',
            'resume' => $resume->load(['user:id,name', 'book:id,title']),
        ], Response::HTTP_OK);
    }

    /**
     * Grade a student resume. Only teachers / admins are allowed.
     */
    public function grade(Request $request, BookResume $resume): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user, Response::HTTP_UNAUTHORIZED, 'Token tidak valid.');
        abort_if(! in_array($user->role, ['GURU', 'ADMIN'], true), Response::HTTP_FORBIDDEN, 'Hanya guru yang dapat memberikan nilai.');

        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'teacher_feedback' => ['nullable', 'string'],
        ]);

        $resume->update([
            'score' => $validated['score'],
            'teacher_feedback' => $validated['teacher_feedback'] ?? null,
            'graded_by' => $user->id,
            'graded_at' => now(),
        ]);

        return response()->json([
            'message' => 'Nilai resume berhasil disimpan.',
            'resume' => $resume->fresh(['user:id,name,email', 'book:id,title', 'grader:id,name']),
        ], Response::HTTP_OK);
    }

    /**
     * Delete a resume.
     */
    public function destroy(Request $request, BookResume $resume): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user, Response::HTTP_UNAUTHORIZED, 'Token tidak valid.');

        if ($user->role === 'SISWA' && $resume->user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki hak untuk menghapus resume ini.');
        }

        $resume->delete();

        return response()->json([
            'message' => 'Resume berhasil dihapus.',
        ]);
    }
}
