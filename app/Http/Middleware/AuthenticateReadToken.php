<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentikasi API baca-saja untuk app lain.
 * Header: Authorization: Bearer <token>
 * Token valid diambil dari SOKROSOK_READ_TOKENS (boleh banyak, dipisah koma, untuk rotasi).
 */
class AuthenticateReadToken
{
    /** Panjang minimum token; token yang lebih pendek diabaikan demi keamanan. */
    private const MIN_LENGTH = 40;

    public function handle(Request $request, Closure $next): Response
    {
        $given = (string) $request->bearerToken();

        if ($given === '' || ! $this->isValid($given)) {
            return response()->json(['message' => 'Unauthorized'], 401, ['WWW-Authenticate' => 'Bearer']);
        }

        return $next($request);
    }

    private function isValid(string $given): bool
    {
        $valid = false;

        // Bandingkan ke semua token tanpa berhenti di yang pertama cocok.
        foreach ((array) config('services.sokrosok.read_tokens', []) as $token) {
            if (strlen($token) >= self::MIN_LENGTH && hash_equals($token, $given)) {
                $valid = true;
            }
        }

        return $valid;
    }
}