<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Validates the API key from the X-Api-Key header.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $configuredKey = config('gateway.api_key');

        // If no API key is configured, skip validation
        if (empty($configuredKey)) {
            return $next($request);
        }

        // Check header only
        $providedKey = $request->header('X-Api-Key');

        if (empty($providedKey) || $providedKey !== $configuredKey) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing API key. Provide via X-Api-Key header.',
            ], 401);
        }

        return $next($request);
    }
}
