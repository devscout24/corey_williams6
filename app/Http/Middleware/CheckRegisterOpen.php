<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\PhpposRegister;
use App\Models\PhpposRegisterLog;
use App\Services\EmployeeService;
use Symfony\Component\HttpFoundation\Response;

class CheckRegisterOpen
{
    public function __construct(private readonly EmployeeService $employeeService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Enforce employee auth
        if (!auth('employee')->check()) {
            return $next($request);
        }

        // 2. Allow opening/changing register routes to bypass
        if ($request->is('sales/register/open') || $request->is('sales/register/change')) {
            return $next($request);
        }

        $employeeId = auth('employee')->id();
        $locationId = session('employee_current_location_id') ?? auth('employee')->user()?->location_id ?? 1;

        // 3. Resolve register_id
        $registerId = Session::get('register_id');
        if (!$registerId) {
            $defaultReg = $this->employeeService->getDefaultRegister($employeeId, $locationId);
            if ($defaultReg) {
                $registerId = $defaultReg['register_id'];
            } else {
                $firstReg = PhpposRegister::where('location_id', $locationId)->where('deleted', 0)->first();
                if ($firstReg) {
                    $registerId = $firstReg->register_id;
                } else {
                    // Create default register if none exists
                    $newReg = PhpposRegister::create([
                        'location_id' => $locationId,
                        'name' => 'Default Register',
                        'deleted' => 0
                    ]);
                    $registerId = $newReg->register_id;
                }
            }
            Session::put('register_id', $registerId);
        }

        // 4. Check if the register is open
        $openLog = PhpposRegisterLog::where('register_id', $registerId)
            ->whereNull('shift_end')
            ->first();

        if (!$openLog) {
            // Register is closed! Redirect to open register page
            return redirect()->route('sales.register.open');
        }

        // Store log ID in session
        Session::put('register_log_id', $openLog->register_log_id);

        return $next($request);
    }
}
