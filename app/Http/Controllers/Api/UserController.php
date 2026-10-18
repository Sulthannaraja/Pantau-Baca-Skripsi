<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request->user());

        $users = User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $actor = $request->user();
        $this->ensureAdmin($actor);

        $payload = $request->validated();

        if ($actor->is($user) && isset($payload['role']) && $payload['role'] !== 'ADMIN') {
            return new JsonResponse([
                'message' => 'Role akun admin aktif tidak bisa diturunkan.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (blank($payload['password'] ?? null)) {
            unset($payload['password']);
        }

        $user->update($payload);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        $this->ensureAdmin($actor);

        abort_if($actor->is($user), Response::HTTP_UNPROCESSABLE_ENTITY, 'Akun yang sedang dipakai tidak bisa dihapus.');

        $user->delete();

        return response()->json(['message' => 'User berhasil dihapus.']);
    }

    private function ensureAdmin(?User $user): void
    {
        abort_if(! $user || $user->role !== 'ADMIN', Response::HTTP_FORBIDDEN, 'Akses admin diperlukan.');
    }
}
