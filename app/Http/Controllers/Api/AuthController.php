<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        User::query()->create($request->validated());

        return new JsonResponse([
            'message' => 'Registrasi berhasil.',
        ], Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::query()
            ->where('email', $credentials['email'])
            ->orWhere('name', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return new JsonResponse([
                'error' => 'Email/username atau password salah.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user->forceFill([
            'api_token' => Str::random(60),
        ])->save();

        return response()->json([
            'token' => $user->api_token,
            'role' => $user->role,
            'userId' => $user->id,
            'name' => $user->name,
        ]);
    }
}
