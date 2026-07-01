<?php

namespace App\Http\Controllers;

use App\Models\PhpposEmployee;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(): View
    {
        $employees = PhpposEmployee::query()
            ->with('person')
            ->where('deleted', 0)
            ->orderBy('person_id')
            ->paginate(20);

        return view('employees.index', compact('employees'));
    }

    public function create(): View
    {
        $locations = DB::table('phppos_locations')
            ->where('deleted', 0)
            ->orderBy('location_id')
            ->get();

        $modules = DB::table('phppos_modules')
            ->orderBy('sort')
            ->get();

        $moduleActions = DB::table('phppos_modules_actions')
            ->orderBy('module_id')
            ->orderBy('action_id')
            ->get()
            ->groupBy('module_id');

        $permissionTemplates = DB::table('phppos_permissions_templates')
            ->where('deleted', 0)
            ->orderBy('name')
            ->get();

        return view('employees.form', [
            'employee' => null,
            'person' => null,
            'locations' => $locations,
            'selectedLocations' => [],
            'modules' => $modules,
            'moduleActions' => $moduleActions,
            'permissionTemplates' => $permissionTemplates,
            'selectedPermissions' => [],
            'selectedActionPermissions' => [],
            'selectedModuleLocations' => [],
            'selectedActionLocations' => [],
        ]);
    }

    public function edit(int $employeeId): View
    {
        $employee = PhpposEmployee::query()
            ->with('person')
            ->where('person_id', $employeeId)
            ->firstOrFail();

        $locations = DB::table('phppos_locations')
            ->where('deleted', 0)
            ->orderBy('location_id')
            ->get();

        $selectedLocations = DB::table('phppos_employees_locations')
            ->where('employee_id', $employeeId)
            ->pluck('location_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $modules = DB::table('phppos_modules')
            ->orderBy('sort')
            ->get();

        $moduleActions = DB::table('phppos_modules_actions')
            ->orderBy('module_id')
            ->orderBy('action_id')
            ->get()
            ->groupBy('module_id');

        $permissionTemplates = DB::table('phppos_permissions_templates')
            ->where('deleted', 0)
            ->orderBy('name')
            ->get();

        // Get selected permissions
        $selectedPermissions = DB::table('phppos_permissions')
            ->where('person_id', $employeeId)
            ->pluck('module_id')
            ->all();

        // Get selected action permissions
        $selectedActionPermissions = DB::table('phppos_permissions_actions')
            ->where('person_id', $employeeId)
            ->get()
            ->map(fn ($row) => $row->module_id . '|' . $row->action_id)
            ->all();

        // Get selected module locations
        $selectedModuleLocations = DB::table('phppos_permissions_locations')
            ->where('person_id', $employeeId)
            ->get()
            ->map(fn ($row) => $row->module_id . '|' . $row->location_id)
            ->all();

        // Get selected action locations
        $selectedActionLocations = DB::table('phppos_permissions_actions_locations')
            ->where('person_id', $employeeId)
            ->get()
            ->map(fn ($row) => $row->module_id . '|' . $row->action_id . '|' . $row->location_id)
            ->all();

        return view('employees.form', [
            'employee' => $employee,
            'person' => $employee->person,
            'locations' => $locations,
            'selectedLocations' => $selectedLocations,
            'modules' => $modules,
            'moduleActions' => $moduleActions,
            'permissionTemplates' => $permissionTemplates,
            'selectedPermissions' => $selectedPermissions,
            'selectedActionPermissions' => $selectedActionPermissions,
            'selectedModuleLocations' => $selectedModuleLocations,
            'selectedActionLocations' => $selectedActionLocations,
        ]);
    }

    public function store(Request $request, EmployeeService $employeeService): RedirectResponse
    {
        return $this->saveEmployee($request, $employeeService, null);
    }

    public function update(Request $request, EmployeeService $employeeService, int $employeeId): RedirectResponse
    {
        return $this->saveEmployee($request, $employeeService, $employeeId);
    }

    public function profile(): View
    {
        $employee = auth('employee')->user()->load('person');

        return view('employees.profile', compact('employee'));
    }

    public function updateProfile(Request $request)
    {
        $employee = auth('employee')->user();
        $person = $employee->person;

        if ($request->boolean('change_password')) {
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ]);

            if (! $employee->validatePassword($validated['current_password'])) {
                return response()->json(['message' => 'Current password is incorrect.'], 422);
            }

            $employee->password = Hash::make($validated['new_password']);
            $employee->save();

            return response()->json(['message' => 'Password updated successfully.'], 200);
        }

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'employee_number' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
        ]);

        try {
            if ($person) {
                $person->update([
                    'first_name' => $validated['first_name'] ?? $person->first_name,
                    'last_name' => $validated['last_name'] ?? $person->last_name,
                    'email' => $validated['email'] ?? $person->email,
                    'phone_number' => $validated['phone_number'] ?? $person->phone_number,
                    'title' => $validated['title'] ?? $person->title,
                ]);
            }

            if (isset($validated['username'])) {
                $employee->update(['username' => $validated['username']]);
            }

            if (isset($validated['employee_number'])) {
                $employee->update(['employee_number' => $validated['employee_number']]);
            }

            return response()->json(['message' => 'Profile updated successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error updating profile: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(int $employeeId): RedirectResponse
    {
        PhpposEmployee::query()
            ->where('person_id', $employeeId)
            ->update(['deleted' => 1]);

        return redirect()->route('employees.index')->with('status', 'Employee archived.');
    }

    private function saveEmployee(Request $request, EmployeeService $employeeService, ?int $employeeId): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'address_1' => ['nullable', 'string', 'max:255'],
            'address_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'comments' => ['nullable', 'string', 'max:1000'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'employee_number' => ['nullable', 'string', 'max:255'],
            'hire_date' => ['nullable', 'date'],
            'birthday' => ['nullable', 'date'],
            'termination_date' => ['nullable', 'date'],
            'inactive' => ['nullable', 'boolean'],
            'force_password_change' => ['nullable', 'boolean'],
            'always_require_password' => ['nullable', 'boolean'],
            'not_required_to_clock_in' => ['nullable', 'boolean'],
            'max_discount_percent' => ['nullable', 'numeric'],
            'login_start_time' => ['nullable', 'date_format:H:i'],
            'login_end_time' => ['nullable', 'date_format:H:i'],
            'commission_percent' => ['nullable', 'numeric'],
            'commission_percent_type' => ['nullable', 'string', 'max:255'],
            'hourly_pay_rate' => ['nullable', 'numeric'],
            'allowed_ip_address' => ['nullable', 'string', 'max:1000'],
            'locations' => ['nullable', 'array'],
            'locations.*' => ['integer'],
            'permissions' => ['nullable', 'array'],
            'permissions_actions' => ['nullable', 'array'],
            'module_location' => ['nullable', 'array'],
            'action_location' => ['nullable', 'array'],
            'permission_templates' => ['nullable', 'integer'],
        ]);

        $personData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? '',
            'phone_number' => $data['phone_number'] ?? '',
            'address_1' => $data['address_1'] ?? '',
            'address_2' => $data['address_2'] ?? '',
            'city' => $data['city'] ?? '',
            'state' => $data['state'] ?? '',
            'zip' => $data['zip'] ?? '',
            'country' => $data['country'] ?? '',
            'comments' => $data['comments'] ?? '',
        ];

        $employeeData = [
            'username' => $data['username'],
            'inactive' => ! empty($data['inactive']) ? 1 : 0,
            'reason_inactive' => null,
            'allowed_ip_address' => isset($data['allowed_ip_address'])
                ? serialize(array_filter(array_map('trim', explode(',', $data['allowed_ip_address']))))
                : serialize([]),
            'hire_date' => $data['hire_date'] ?? null,
            'employee_number' => $data['employee_number'] ?? null,
            'birthday' => $data['birthday'] ?? null,
            'termination_date' => $data['termination_date'] ?? null,
            'force_password_change' => ! empty($data['force_password_change']) ? 1 : 0,
            'always_require_password' => ! empty($data['always_require_password']) ? 1 : 0,
            'not_required_to_clock_in' => ! empty($data['not_required_to_clock_in']) ? 1 : 0,
            'max_discount_percent' => $data['max_discount_percent'] ?? null,
            'login_start_time' => $data['login_start_time'] ?? null,
            'login_end_time' => $data['login_end_time'] ?? null,
            'commission_percent' => $data['commission_percent'] ?? 0,
            'commission_percent_type' => $data['commission_percent_type'] ?? '',
            'hourly_pay_rate' => $data['hourly_pay_rate'] ?? 0,
            'template_id' => $data['permission_templates'] ?? null,
        ];

        if (! empty($data['password'])) {
            $employeeData['password'] = md5($data['password']);
        }

        // Build permission data from form
        $permissionData = $data['permissions'] ?? [];
        $permissionActionData = $data['permissions_actions'] ?? [];
        $locationData = $data['locations'] ?? [];
        $moduleLocations = [];
        foreach ($data['module_location'] ?? [] as $loc) {
            $moduleLocations[] = $loc;
        }
        $actionLocations = [];
        foreach ($data['action_location'] ?? [] as $loc) {
            $actionLocations[] = $loc;
        }

        $employeeService->saveEmployee(
            $personData,
            $employeeData,
            $permissionData,
            $permissionActionData,
            $locationData,
            $employeeId,
            $actionLocations,
            $moduleLocations,
        );

        return redirect()->route('employees.index')->with('status', 'Employee saved.');
    }
}
