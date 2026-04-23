<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Validates the API key from either the X-Api-Key header
     * or the api_key query parameter.
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

        // Check header first, then query parameter
        $providedKey = $request->header('X-Api-Key')
            ?? $request->query('api_key');

        if (empty($providedKey) || $providedKey !== $configuredKey) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing API key. Provide via X-Api-Key header or api_key query parameter.',
            ], 401);
        }

        return $next($request);
    }
}
