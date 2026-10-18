<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?: $request->query('token');

        if (! $token) {
            return new JsonResponse(['error' => 'Token tidak ditemukan.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = User::query()->where('api_token', $token)->first();

        if (! $user) {
            return new JsonResponse(['error' => 'Token tidak valid.'], Response::HTTP_UNAUTHORIZED);
        }

        $request->setUserResolver(static fn (): User => $user);

        return $next($request);
    }
}
