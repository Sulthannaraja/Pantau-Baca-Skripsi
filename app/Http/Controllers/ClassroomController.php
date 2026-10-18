<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ClassroomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user, Response::HTTP_UNAUTHORIZED, 'Token tidak valid.');

        if ($user->role === 'GURU') {
            $classes = Classroom::query()
                ->where('teacher_id', $user->id)
                ->with(['users:id,name,email'])
                ->withCount('users')
                ->latest()
                ->get();
        } elseif ($user->role === 'ADMIN') {
            $classes = Classroom::query()
                ->with(['teacher:id,name,email', 'users:id,name,email'])
                ->withCount('users')
                ->latest()
                ->get();
        } else {
            $classes = $user->classrooms()
                ->with('teacher:id,name,email')
                ->withCount('users')
                ->latest()
                ->get();
        }

        return response()->json($classes);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user, Response::HTTP_UNAUTHORIZED, 'Token tidak valid.');
        abort_if(! in_array($user->role, ['GURU', 'ADMIN'], true), Response::HTTP_FORBIDDEN, 'Hanya guru yang dapat membuat kelas.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $classroom = Classroom::create([
            'name' => $validated['name'],
            'teacher_id' => $user->id,
            'code' => strtoupper(Str::random(6)),
        ]);

        return response()->json([
            'message' => 'Kelas berhasil dibuat.',
            'data' => $classroom->load('teacher:id,name'),
        ], Response::HTTP_CREATED);
    }

    public function join(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user, Response::HTTP_UNAUTHORIZED, 'Token tidak valid.');
        abort_if($user->role !== 'SISWA', Response::HTTP_FORBIDDEN, 'Hanya siswa yang dapat bergabung ke kelas.');

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = strtoupper(trim($validated['code']));
        $classroom = Classroom::where('code', $code)->first();

        if (! $classroom) {
            return response()->json([
                'message' => 'Kode kelas tidak ditemukan. Pastikan kode kelas yang dimasukkan sudah benar.',
            ], Response::HTTP_NOT_FOUND);
        }

        $classroom->users()->syncWithoutDetaching([$user->id]);

        return response()->json([
            'message' => "Berhasil bergabung ke kelas {$classroom->name}.",
            'data' => $classroom->load('teacher:id,name'),
        ], Response::HTTP_OK);
    }

    public function destroy(Request $request, Classroom $classroom): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user, Response::HTTP_UNAUTHORIZED, 'Token tidak valid.');

        if ($user->role !== 'ADMIN' && $classroom->teacher_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak berhak menghapus kelas ini.');
        }

        $classroom->delete();

        return response()->json([
            'message' => 'Kelas berhasil dihapus.',
        ], Response::HTTP_OK);
    }
}
