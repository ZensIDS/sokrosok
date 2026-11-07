<?php

namespace App\Http\Middleware;

use App\Models\Bin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Autentikasi perangkat: x-api-key harus cocok dengan key milik bin_code tsb. */
class AuthenticateBin
{
    public function handle(Request $request, Closure $next): Response
    {
        $code = strtoupper(trim((string) $request->input('bin_code')));
        $key = (string) $request->header('x-api-key');

        $bin = $code !== '' ? Bin::where('code', $code)->where('is_active', true)->first() : null;

        if (! $bin || $key === '' || ! hash_equals($bin->api_key, $key)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->attributes->set('bin', $bin);

        return $next($request);
    }
}