<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
      */
    // protected function redirectTo(Request $request): ?string
    // {
    //     return $request->expectsJson() ? null : route('login');
    // }

    protected function redirectTo(Request $request): ?string
    {
        // If the request expects JSON (API request), return a 401 response
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Session expired'], 401);
        }

        // For non-API requests (web routes), redirect to the login page
        return response()->json(['message' => 'Session expired'], 401);
    }
}
