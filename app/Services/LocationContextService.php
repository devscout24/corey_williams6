<?php

namespace App\Services;

use App\Models\PhpposLocation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LocationContextService
{
    public function resolveLocationId(?int $requestedLocationId = null): int
    {
        $ulid = request()->header('X-Location-ULID') ?? request()->cookie('location_ulid');
        if ($ulid) {
            $location = PhpposLocation::where('ulid', $ulid)->first();
            if ($location) {
                return (int) $location->location_id;
            }
        }

        if ($requestedLocationId && $requestedLocationId > 0) {
            $exists = DB::table('phppos_locations')
                ->where('location_id', $requestedLocationId)
                ->exists();

            if ($exists) {
                return $requestedLocationId;
            }
        }

        $employeeId = auth('employee')->id();
        if ($employeeId) {
            $employeeService = app(EmployeeService::class);
            $employeeLocationId = $employeeService->getLoggedInEmployeeCurrentLocationId();
            if ($employeeLocationId) {
                return (int) $employeeLocationId;
            }
        }

        $fallback = DB::table('phppos_locations')
            ->orderBy('location_id')
            ->value('location_id');

        if (! $fallback) {
            throw new RuntimeException('No location found for current POS node.');
        }

        return (int) $fallback;
    }
}
