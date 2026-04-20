<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        

        $validApiKey = config('app.api_key');

        if (!$validApiKey) {
            // If API key is not set in server, we might want to allow or fail. 
            // Failsafe: if not configured, block everything or allow? 
            // Usually block to force configuration.
            return response()->json(['message' => 'Server API Key not configured'], 500);
        }

        if ($request->header('x-api-key') !== $validApiKey) {
            return response()->json(['message' => 'Invalid API Key'], 401);
        }

        return $next($request);
    }
}
