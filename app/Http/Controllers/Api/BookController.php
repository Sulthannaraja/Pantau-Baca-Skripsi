<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Book::query();

        // If bearer token is supplied, resolve user if not already set
        $user = $request->user();
        if (! $user && $token = $request->bearerToken()) {
            $user = User::query()->where('api_token', $token)->first();
        }

        // Optional filter by literacy_level (e.g. ?literacy_level=RENDAH or ?literacy_level=TINGGI)
        if ($request->filled('literacy_level')) {
            $query->forLiteracyLevel((string) $request->query('literacy_level'));
        } elseif ($request->boolean('auto_recommend') && $user?->isSiswa()) {
            // Auto match with student's latest survey result
            $survey = $user->surveys()->latest('id')->first();
            if ($survey && $survey->level) {
                $query->forLiteracyLevel($survey->level);
            }
        }

        // Optional search filter
        if ($request->filled('search')) {
            $search = '%'.$request->query('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('author', 'like', $search)
                    ->orWhere('category', 'like', $search);
            });
        }

        $books = $query
            ->latest('id')
            ->get()
            ->map(fn (Book $book): array => $this->transformBook($book))
            ->all();

        return response()->json($books);
    }

    public function show(Book $book): JsonResponse
    {
        return response()->json($this->transformBook($book));
    }

    public function cover(Book $book): Response
    {
        $coverFile = public_path('covers/cover_'.$book->id.'.jpg');
        if (file_exists($coverFile)) {
            if ($book->cover !== '/covers/cover_'.$book->id.'.jpg') {
                $book->update(['cover' => '/covers/cover_'.$book->id.'.jpg']);
            }

            return response()->file($coverFile, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        if ($book->cover) {
            $trimmed = ltrim($book->cover, '/');
            if (file_exists(public_path($trimmed))) {
                return response()->file(public_path($trimmed), [
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
            if (str_starts_with($book->cover, 'http')) {
                return redirect()->away($book->cover);
            }
        }

        return response($this->generateFallbackSvg($book->title, $book->author, $book->category, $book->literacy_level), 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    public function saveCover(Request $request, Book $book): JsonResponse
    {
        $coversDir = public_path('covers');
        if (! is_dir($coversDir)) {
            mkdir($coversDir, 0755, true);
        }

        $filename = 'cover_'.$book->id.'.jpg';
        $targetPath = $coversDir.DIRECTORY_SEPARATOR.$filename;

        if ($request->hasFile('cover_file')) {
            $request->file('cover_file')->move($coversDir, $filename);
            $book->update(['cover' => '/covers/'.$filename]);
        } elseif ($request->filled('cover_data')) {
            $data = (string) $request->input('cover_data');
            if (str_contains($data, 'base64,')) {
                $data = substr($data, strpos($data, 'base64,') + 7);
            }
            $decoded = base64_decode($data);
            if ($decoded !== false && strlen($decoded) > 100) {
                file_put_contents($targetPath, $decoded);
                $book->update(['cover' => '/covers/'.$filename]);
            }
        } elseif ($request->filled('cover_url')) {
            $book->update(['cover' => (string) $request->input('cover_url')]);
        }

        return response()->json($this->transformBook($book->fresh()));
    }

    public function upload(StoreBookRequest $request): JsonResponse
    {
        $this->ensureAdminOrGuru($request->user());

        $payload = $request->validated();
        $filePath = $request->file('file')->store('books', 'local');

        $book = Book::query()->create([
            'title' => $payload['title'],
            'author' => $payload['author'],
            'year' => isset($payload['year']) && $payload['year'] !== '' ? (int) $payload['year'] : null,
            'category' => $payload['category'] ?? null,
            'cover' => $payload['cover'] ?? null,
            'literacy_level' => Book::normalizeLevel($payload['literacy_level'] ?? null) ?? 'RENDAH',
            'file_path' => $filePath,
        ]);

        if ($request->hasFile('cover_file')) {
            $coversDir = public_path('covers');
            if (! is_dir($coversDir)) {
                mkdir($coversDir, 0755, true);
            }
            $filename = 'cover_'.$book->id.'.jpg';
            $request->file('cover_file')->move($coversDir, $filename);
            $book->update(['cover' => '/covers/'.$filename]);
        } elseif ($request->filled('cover_data')) {
            $coversDir = public_path('covers');
            if (! is_dir($coversDir)) {
                mkdir($coversDir, 0755, true);
            }
            $filename = 'cover_'.$book->id.'.jpg';
            $data = (string) $request->input('cover_data');
            if (str_contains($data, 'base64,')) {
                $data = substr($data, strpos($data, 'base64,') + 7);
            }
            $decoded = base64_decode($data);
            if ($decoded !== false && strlen($decoded) > 100) {
                file_put_contents($coversDir.DIRECTORY_SEPARATOR.$filename, $decoded);
                $book->update(['cover' => '/covers/'.$filename]);
            }
        }

        return new JsonResponse($this->transformBook($book->fresh()), Response::HTTP_CREATED);
    }

    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $this->ensureAdminOrGuru($request->user());

        $payload = $request->validated();

        if ($request->hasFile('file')) {
            Storage::disk('local')->delete($book->file_path);
            $payload['file_path'] = $request->file('file')->store('books', 'local');
            unset($payload['file']);
        }

        if (isset($payload['literacy_level'])) {
            $payload['literacy_level'] = Book::normalizeLevel($payload['literacy_level']) ?? 'RENDAH';
        }

        $book->update($payload);

        return response()->json($this->transformBook($book->fresh()));
    }

    public function destroy(Request $request, Book $book): JsonResponse
    {
        $this->ensureAdminOrGuru($request->user());

        Storage::disk('local')->delete($book->file_path);
        $book->delete();

        return response()->json(['message' => 'Buku berhasil dihapus.']);
    }

    public function pageMeta(Request $request, Book $book): JsonResponse
    {
        $this->ensureAuthenticated($request->user());

        return response()->json([
            'totalPages' => 1,
        ]);
    }

    public function pageImage(Request $request, Book $book, int $page): JsonResponse
    {
        $this->ensureAuthenticated($request->user());

        return new JsonResponse([
            'error' => 'Preview halaman belum tersedia. Reader akan memakai mode stream PDF.',
        ], Response::HTTP_NOT_FOUND);
    }

    public function stream(Request $request, Book $book): BinaryFileResponse
    {
        $this->ensureAuthenticated($request->user());

        abort_unless(Storage::disk('local')->exists($book->file_path), Response::HTTP_NOT_FOUND);

        $absolutePath = Storage::disk('local')->path($book->file_path);

        return response()->file($absolutePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($absolutePath).'"',
        ]);
    }

    private function transformBook(Book $book): array
    {
        $cover = $book->cover;
        if (! $cover && file_exists(public_path('covers/cover_'.$book->id.'.jpg'))) {
            $cover = '/covers/cover_'.$book->id.'.jpg';
        }
        if ($cover && ! str_starts_with($cover, 'http') && ! str_starts_with($cover, 'data:')) {
            $cover = '/'.ltrim($cover, '/');
        }

        return [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'year' => $book->year,
            'category' => $book->category,
            'cover' => $cover,
            'literacy_level' => Book::normalizeLevel($book->literacy_level) ?? 'RENDAH',
        ];
    }

    private function generateFallbackSvg(string $title, string $author, ?string $category = null, ?string $level = null): string
    {
        $safeTitle = htmlspecialchars(mb_strimwidth($title, 0, 48, '...'), ENT_QUOTES, 'UTF-8');
        $safeAuthor = htmlspecialchars(mb_strimwidth($author ?: 'Penulis', 0, 32, '...'), ENT_QUOTES, 'UTF-8');
        $safeCategory = htmlspecialchars($category ?: 'PantauBaca', ENT_QUOTES, 'UTF-8');
        $isLanjutan = in_array(strtoupper((string) $level), ['TINGGI', 'LANJUTAN'], true);

        $bgStart = $isLanjutan ? '#14532D' : '#1E3A8A';
        $bgEnd = $isLanjutan ? '#064E3B' : '#0F172A';

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 400" width="100%" height="100%">
  <defs>
    <linearGradient id="coverBg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$bgStart}"/>
      <stop offset="100%" stop-color="{$bgEnd}"/>
    </linearGradient>
    <linearGradient id="goldAcc" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#F59E0B"/>
      <stop offset="100%" stop-color="#FDE68A"/>
    </linearGradient>
    <linearGradient id="spineHighlight" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.22"/>
      <stop offset="50%" stop-color="#000000" stop-opacity="0.3"/>
      <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0.05"/>
    </linearGradient>
  </defs>

  <rect width="300" height="400" fill="url(#coverBg)" rx="8"/>
  <rect x="0" y="0" width="22" height="400" fill="url(#spineHighlight)"/>
  <line x1="22" y1="0" x2="22" y2="400" stroke="#000000" stroke-opacity="0.4" stroke-width="1.5"/>
  <line x1="23.5" y1="0" x2="23.5" y2="400" stroke="#FFFFFF" stroke-opacity="0.15" stroke-width="1"/>

  <rect x="34" y="24" width="236" height="352" fill="none" stroke="url(#goldAcc)" stroke-width="1.5" stroke-opacity="0.6" rx="4"/>
  <rect x="39" y="29" width="226" height="342" fill="none" stroke="#FFFFFF" stroke-width="0.75" stroke-opacity="0.2" rx="2"/>

  <rect x="75" y="48" width="150" height="20" rx="10" fill="rgba(255,255,255,0.12)"/>
  <text x="150" y="62" fill="#E2E8F0" font-family="'Plus Jakarta Sans', sans-serif" font-size="9" font-weight="700" letter-spacing="1.5" text-anchor="middle">{$safeCategory}</text>

  <circle cx="150" cy="120" r="34" fill="rgba(255,255,255,0.06)" stroke="url(#goldAcc)" stroke-width="1.5"/>
  <path d="M136 112c5-3 9-3 14 1 5-4 9-4 14-1v18c-5-3-9-3-14 1-5-4-9-4-14-1z" fill="#FBBF24"/>
  <path d="M150 113v19" stroke="#B45309" stroke-width="1.5"/>

  <foreignObject x="45" y="170" width="210" height="110">
    <div xmlns="http://www.w3.org/1999/xhtml" style="color:#FFFFFF;font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;font-weight:800;line-height:1.35;text-align:center;word-break:break-word;display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden;padding:0 4px;">
      {$safeTitle}
    </div>
  </foreignObject>

  <line x1="110" y1="292" x2="190" y2="292" stroke="url(#goldAcc)" stroke-width="1" stroke-opacity="0.8"/>
  <text x="150" y="314" fill="#CBD5E1" font-family="'Plus Jakarta Sans', sans-serif" font-size="11" font-weight="600" text-anchor="middle">✍️ {$safeAuthor}</text>

  <rect x="70" y="342" width="160" height="22" rx="11" fill="rgba(15,23,42,0.6)"/>
  <text x="150" y="357" fill="#FCD34D" font-family="'Plus Jakarta Sans', sans-serif" font-size="9.5" font-weight="700" letter-spacing="0.5" text-anchor="middle">📖 PantauBaca Digital</text>
</svg>
SVG;
    }

    private function ensureAuthenticated(?User $user): void
    {
        abort_if(! $user, Response::HTTP_UNAUTHORIZED, 'Token tidak valid.');
    }

    private function ensureAdminOrGuru(?User $user): void
    {
        abort_if(! $user || ! in_array($user->role, ['ADMIN', 'GURU'], true), Response::HTTP_FORBIDDEN, 'Akses guru atau admin diperlukan.');
    }
}
