<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('sync.shared_token');
        if (! $token) {
            return response()->json(['message' => 'Sync token not configured.'], 500);
        }

        $provided = $request->header('X-Sync-Token') ?? $request->query('sync_token');
        if (! is_string($provided) || ! hash_equals($token, $provided)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
