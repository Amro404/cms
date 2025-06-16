<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitByRole
{
    private const RATE_LIMITS = [
        'admin' => 1000,
        'editor' => 500,
        'author' => 200,
        'viewer' => 60
    ];
    
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return $next($request);
        }

        $limits = self::RATE_LIMITS;

        $userRole = $user->getRoleNames()->first() ?? 'viewer';
        $maxAttempts = $limits[$userRole] ?? 60;

        $key = 'api_rate_limit:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.'
            ], 429);
        }

        RateLimiter::hit($key, 60); // 1 minute window

        return $next($request);
    }
}
