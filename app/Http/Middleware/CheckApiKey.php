<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        // ✅ 1. PERMITIR PETICIONES OPTIONS (CORS)
        // Las peticiones Preflight no envían cabeceras personalizadas
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        $validApiKey = config('app.api_key');

        if (!$validApiKey) {
            return response()->json(['message' => 'Server API Key not configured'], 500);
        }

        // ✅ 2. VALIDACIÓN DE LA CLAVE
        if ($request->header('x-api-key') !== $validApiKey) {
            return response()->json(['message' => 'Invalid API Key'], 401);
        }

        return $next($request);
    }
}
