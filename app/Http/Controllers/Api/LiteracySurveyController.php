<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiteracySurvey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LiteracySurveyController extends Controller
{
    /**
     * Submit student's 10-question digital literacy assessment.
     */
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'responses' => ['required', 'array', 'size:10'],
            'responses.*' => ['required', 'integer', 'between:1,4'],
        ]);

        $responses = $validated['responses'];
        $totalScore = array_sum($responses);

        // Standard: Score >= 28 (70%) => TINGGI, otherwise RENDAH
        $level = $totalScore >= 28 ? 'TINGGI' : 'RENDAH';

        $userId = $request->user()?->id ?: $request->input('user_id');

        $survey = LiteracySurvey::query()->create([
            'user_id' => $userId,
            'responses' => $responses,
            'total_score' => $totalScore,
            'level' => $level,
        ]);

        return response()->json([
            'message' => 'Angket literasi digital berhasil dievaluasi.',
            'total_score' => $totalScore,
            'max_score' => 40,
            'percentage' => round(($totalScore / 40) * 100, 1),
            'level' => $level,
            'level_label' => $level === 'TINGGI' ? 'Tingkat Tinggi (Kategori Lanjutan)' : 'Tingkat Rendah (Kategori Dasar)',
            'survey' => $survey,
        ], Response::HTTP_CREATED);
    }

    /**
     * Get student's latest assessment result.
     */
    public function myResult(Request $request): JsonResponse
    {
        $userId = $request->user()?->id ?: $request->input('user_id');

        $latest = LiteracySurvey::query()
            ->where('user_id', $userId)
            ->latest()
            ->first();

        if (! $latest) {
            return response()->json([
                'has_submitted' => false,
            ]);
        }

        return response()->json([
            'has_submitted' => true,
            'total_score' => $latest->total_score,
            'max_score' => 40,
            'percentage' => round(($latest->total_score / 40) * 100, 1),
            'level' => $latest->level,
            'level_label' => $latest->level === 'TINGGI' ? 'Tingkat Tinggi (Kategori Lanjutan)' : 'Tingkat Rendah (Kategori Dasar)',
            'updated_at' => $latest->created_at,
        ]);
    }

    /**
     * Get all survey results for teachers / admin.
     */
    public function index(): JsonResponse
    {
        $surveys = LiteracySurvey::query()
            ->with('user:id,name,email')
            ->latest()
            ->get();

        return response()->json($surveys);
    }
}
