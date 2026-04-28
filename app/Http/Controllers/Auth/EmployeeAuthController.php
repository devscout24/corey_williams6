<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PhpposEmployee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployeeAuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.employee-login');
    }

    /**
     * Legacy-compatible employee login (username or employee_number).
     *
     * @throws ValidationException
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $employee = PhpposEmployee::findActiveByLogin($credentials['login']);

        if (! $employee || ! $employee->canLoginNow() || ! $employee->validatePassword($credentials['password'])) {
            throw ValidationException::withMessages([
                'login' => 'The provided credentials are incorrect.',
            ]);
        }

        Auth::guard('employee')->login($employee, (bool) ($credentials['remember'] ?? false));
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('employee')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
