<?php

namespace App\Providers;

use App\Models\Location;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

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

        if (!Schema::hasTable('locations')) {
            return;
        }

        $nodeIp = config('app.node_ip');
        if (!$nodeIp) {
            return;
        }

        $nodeName = config('app.node_name');

        $location = Location::where('is_self', true)->first();
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
