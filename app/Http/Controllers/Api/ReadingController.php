<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StartReadingRequest;
use App\Http\Requests\UpdateReadingProgressRequest;
use App\Models\ReadingLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadingController extends Controller
{
    public function start(StartReadingRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureStudent($user);

        $payload = $request->validated();

        $readingLog = ReadingLog::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'book_id' => $payload['bookId'],
            ],
            [
                'duration' => 0,
                'progress' => 0,
                'last_page' => $payload['currentPage'] ?? 1,
                'started_at' => now(),
            ],
        );

        return response()->json($this->transformLog($readingLog));
    }

    public function progress(UpdateReadingProgressRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->ensureStudent($user);

        $payload = $request->validated();

        $readingLog = ReadingLog::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'book_id' => $payload['bookId'],
            ],
            [
                'started_at' => now(),
            ],
        );

        $readingLog->update([
            'duration' => $payload['duration'],
            'progress' => $payload['progress'],
            'last_page' => $payload['lastPage'],
        ]);

        return response()->json($this->transformLog($readingLog->fresh()));
    }

    public function report(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user, Response::HTTP_UNAUTHORIZED, 'Token tidak valid.');

        $query = ReadingLog::query()
            ->with(['user:id,name', 'book:id,title'])
            ->latest('updated_at');

        if ($user->role === 'SISWA') {
            $query->whereBelongsTo($user);
        }

        $logs = $query->get()->map(fn (ReadingLog $log): array => [
            'logId' => $log->id,
            'userId' => $log->user_id,
            'userName' => $log->user?->name,
            'bookId' => $log->book_id,
            'bookTitle' => $log->book?->title,
            'duration' => $log->duration,
            'progress' => $log->progress,
            'lastPage' => $log->last_page,
            'updatedAt' => optional($log->updated_at)->toIso8601String(),
        ])->all();

        return response()->json($logs);
    }

    private function transformLog(ReadingLog $readingLog): array
    {
        return [
            'duration' => $readingLog->duration,
            'progress' => $readingLog->progress,
            'lastPage' => $readingLog->last_page,
        ];
    }

    private function ensureStudent(?User $user): void
    {
        abort_if(! $user || $user->role !== 'SISWA', Response::HTTP_FORBIDDEN, 'Akses siswa diperlukan.');
    }
}
