<?php

namespace App\Services;

use App\Models\PhpposEmployee;
use App\Models\PhpposPerson;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    public function saveEmployee(
        array $personData,
        array $employeeData,
        array $permissionData,
        array $permissionActionData,
        array $locationData,
        ?int $employeeId = null,
        array $actionLocations = [],
        array $moduleLocations = [],
    ): int {
        return DB::transaction(function () use (
            $personData,
            $employeeData,
            $permissionData,
            $permissionActionData,
            $locationData,
            $employeeId,
            $actionLocations,
            $moduleLocations,
        ): int {
            $personId = $this->savePerson($personData, $employeeId);
            $this->saveEmployeeRow($employeeData, $personId, $employeeId);

            $this->syncPermissions($personId, $permissionData, $permissionActionData);
            $this->syncLocationPermissions($personId, $moduleLocations, $actionLocations);
            $this->syncEmployeeLocations($personId, $locationData);

            return $personId;
        });
    }

    public function getAuthenticatedLocationIds(int $employeeId): array
    {
        return DB::table('phppos_employees_locations')
            ->where('employee_id', $employeeId)
            ->pluck('location_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    public function hasModuleActionPermission(int $employeeId, string $moduleId, string $actionId): bool
    {
        return DB::table('phppos_permissions_actions')
            ->where('person_id', $employeeId)
            ->where('module_id', $moduleId)
            ->where('action_id', $actionId)
            ->exists();
    }

    public function getDefaultRegister(int $employeeId, ?int $locationId = null): ?array
    {
        $query = DB::table('phppos_employee_registers as er')
            ->join('phppos_registers as r', 'er.register_id', '=', 'r.register_id')
            ->where('er.employee_id', $employeeId);

        if ($locationId !== null) {
            $query->where('r.location_id', $locationId);
        }

        $row = $query->select('er.register_id', 'r.location_id')->first();

        return $row ? (array) $row : null;
    }

    public function getLoggedInEmployeeCurrentLocationId(): ?int
    {
        $employeeId = auth('employee')->id();
        if (! $employeeId) {
            return null;
        }

        // 1. Try to load using ULID (from header or cookie) to support LAN PCs
        $ulid = request()->header('X-Location-ULID') ?? request()->cookie('location_ulid');
        if ($ulid) {
            $location = \App\Models\PhpposLocation::where('ulid', $ulid)->first();
            if ($location) {
                $hasAccess = DB::table('phppos_employees_locations')
                    ->where('employee_id', $employeeId)
                    ->where('location_id', $location->location_id)
                    ->exists();

                if ($hasAccess) {
                    return $location->location_id;
                }
            }
        }

        // 2. Fall back to the legacy method (session)
        $sessionLocationId = session('employee_current_location_id');
        if ($sessionLocationId) {
            return (int) $sessionLocationId;
        }

        // 3. Fall back to the first location assigned to the employee
        return (int) DB::table('phppos_employees_locations')
            ->where('employee_id', $employeeId)
            ->value('location_id') ?: null;
    }

    private function savePerson(array $personData, ?int $employeeId): int
    {
        $personId = $employeeId;

        $firstName = Arr::get($personData, 'first_name');
        $lastName = Arr::get($personData, 'last_name');

        if ($firstName !== null && $lastName !== null) {
            $personData['full_name'] = trim($firstName.' '.$lastName);
        } elseif ($firstName !== null) {
            $current = $employeeId ? PhpposPerson::query()->find($employeeId) : null;
            $personData['full_name'] = trim($firstName.' '.($current?->last_name ?? ''));
        } elseif ($lastName !== null) {
            $current = $employeeId ? PhpposPerson::query()->find($employeeId) : null;
            $personData['full_name'] = trim(($current?->first_name ?? '').' '.$lastName);
        }

        // Set default empty strings for NOT NULL fields (matching CI3 behavior)
        $notNullFields = ['first_name', 'last_name', 'full_name', 'phone_number', 'email', 'address_1', 'address_2', 'city', 'state', 'zip', 'country', 'comments'];
        foreach ($notNullFields as $field) {
            if (!isset($personData[$field]) || $personData[$field] === null) {
                $personData[$field] = '';
            }
        }

        if (! $employeeId || ! PhpposPerson::query()->where('person_id', $employeeId)->exists()) {
            $personData['create_date'] = now();
            $person = PhpposPerson::query()->create($personData);
            $personId = (int) $person->person_id;
        } else {
            $personData['last_modified'] = now();
            PhpposPerson::query()->where('person_id', $employeeId)->update($personData);
        }

        return (int) $personId;
    }

    private function saveEmployeeRow(array $employeeData, int $personId, ?int $employeeId): void
    {
        if (! $employeeId || ! PhpposEmployee::query()->where('person_id', $employeeId)->exists()) {
            $employeeData['person_id'] = $personId;
            PhpposEmployee::query()->create($employeeData);
        } elseif (! empty($employeeData)) {
            PhpposEmployee::query()->where('person_id', $personId)->update($employeeData);
        }
    }

    private function syncPermissions(int $personId, array $permissionData, array $permissionActionData): void
    {
        DB::table('phppos_permissions')->where('person_id', $personId)->delete();
        foreach ($permissionData as $moduleId) {
            DB::table('phppos_permissions')->insert([
                'module_id' => $moduleId,
                'person_id' => $personId,
            ]);
        }

        DB::table('phppos_permissions_actions')->where('person_id', $personId)->delete();
        foreach ($permissionActionData as $permissionAction) {
            if (is_array($permissionAction)) {
                $module = (string) Arr::get($permissionAction, 0, '');
                $action = (string) Arr::get($permissionAction, 1, '');
            } else {
                [$module, $action] = array_pad(explode('|', (string) $permissionAction), 2, '');
            }

            if ($module === '' || $action === '') {
                continue;
            }

            DB::table('phppos_permissions_actions')->insert([
                'module_id' => $module,
                'action_id' => $action,
                'person_id' => $personId,
            ]);
        }
    }

    private function syncLocationPermissions(int $personId, array $moduleLocations, array $actionLocations): void
    {
        DB::table('phppos_permissions_locations')->where('person_id', $personId)->delete();

        $locationRows = [];
        foreach ($moduleLocations as $moduleLocation) {
            $parts = is_array($moduleLocation) ? $moduleLocation : explode('|', (string) $moduleLocation);
            $moduleId = (string) Arr::get($parts, 0, '');
            $locationId = Arr::get($parts, 1);

            if ($moduleId === '' || $locationId === null) {
                continue;
            }

            $locationRows[] = [
                'module_id' => $moduleId,
                'person_id' => $personId,
                'location_id' => (int) $locationId,
            ];
        }

        if ($locationRows) {
            DB::table('phppos_permissions_locations')->insert($locationRows);
        }

        DB::table('phppos_permissions_actions_locations')->where('person_id', $personId)->delete();

        $actionRows = [];
        foreach ($actionLocations as $actionLocation) {
            $parts = is_array($actionLocation) ? $actionLocation : explode('|', (string) $actionLocation);
            $moduleId = (string) Arr::get($parts, 0, '');
            $actionId = (string) Arr::get($parts, 1, '');
            $locationId = Arr::get($parts, 2);

            if ($moduleId === '' || $actionId === '' || $locationId === null) {
                continue;
            }

            $actionRows[] = [
                'module_id' => $moduleId,
                'action_id' => $actionId,
                'location_id' => (int) $locationId,
                'person_id' => $personId,
            ];
        }

        if ($actionRows) {
            DB::table('phppos_permissions_actions_locations')->insert($actionRows);
        }
    }

    private function syncEmployeeLocations(int $personId, array $locationData): void
    {
        DB::table('phppos_employees_locations')->where('employee_id', $personId)->delete();

        foreach ($locationData as $locationId) {
            DB::table('phppos_employees_locations')->insert([
                'employee_id' => $personId,
                'location_id' => (int) $locationId,
            ]);
        }
    }
}
