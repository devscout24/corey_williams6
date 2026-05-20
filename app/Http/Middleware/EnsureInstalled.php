<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isInstalled()) {
            return $next($request);
        }

        if ($request->is('setup') || $request->is('setup/*')) {
            return $next($request);
        }

        return redirect()->route('setup.index');
    }

    private function isInstalled(): bool
    {
        $lockPath = storage_path('app/install.lock');

        return file_exists($lockPath);
    }
}
