<?php

namespace App\Services;

use App\Models\PhpposLocation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LocationContextService
{
    public function resolveLocationId(int|string|null $requestedLocationId = null): int
    {
        $requestedLocationId = $this->normalizeRequestedLocationId($requestedLocationId);

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

    private function normalizeRequestedLocationId(int|string|null $requestedLocationId): ?int
    {
        if (is_int($requestedLocationId)) {
            return $requestedLocationId > 0 ? $requestedLocationId : null;
        }

        if (! is_string($requestedLocationId)) {
            return null;
        }

        $requestedLocationId = trim($requestedLocationId);
        if ($requestedLocationId === '' || strtolower($requestedLocationId) === 'all') {
            return null;
        }

        if (! ctype_digit($requestedLocationId)) {
            return null;
        }

        $asInt = (int) $requestedLocationId;
        return $asInt > 0 ? $asInt : null;
    }
}
