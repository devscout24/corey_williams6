<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModulePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $modules): Response
    {
        $employee = auth('employee')->user();

        if (!$employee) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        // Support multiple comma-separated modules, allow access if the employee has at least one
        $moduleList = array_map('trim', explode(',', $modules));
        $hasPermission = false;

        foreach ($moduleList as $module) {
            if ($employee->hasModulePermission($module)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'You do not have permission to access this module.'], 403);
            }
            abort(403, 'You do not have permission to access this module.');
        }

        return $next($request);
    }
}
