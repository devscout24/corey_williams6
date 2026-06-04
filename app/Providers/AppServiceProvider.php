<?php

namespace App\Providers;

use App\Models\Location;
use App\Services\LocationContextService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        $databasePath = config("database.connections.{$connection}.database");

        if ($driver === 'sqlite' && $databasePath && !file_exists($databasePath)) {
            return;
        }

        if (Schema::hasTable('locations')) {
            $nodeIp = config('app.node_ip');
            if ($nodeIp) {
                $nodeName = config('app.node_name');

                $location = Location::query()->firstWhere('is_self', true);
                if (!$location) {
                    $location = Location::firstOrCreate(
                        ['ip' => $nodeIp],
                        ['name' => $nodeName, 'is_self' => true]
                    );
                }

                $location->name = $nodeName;
                $location->ip = $nodeIp;
                $location->is_self = true;
                $location->save();
            }
        }

        // Share the current store location name (POS location) with all views.
        $storeLocationName = null;
        try {
            if (Schema::hasTable('phppos_locations')) {
                $locationId = app(LocationContextService::class)->resolveLocationId();
                $storeLocationName = DB::table('phppos_locations')
                    ->where('location_id', $locationId)
                    ->value('name');
            }
        } catch (\Throwable $e) {
            $storeLocationName = null;
        }

        View::share('currentStoreLocationName', $storeLocationName);
    }
}
