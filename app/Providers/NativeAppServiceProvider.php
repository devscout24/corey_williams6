<?php

namespace App\Providers;

use App\Models\PhpposAppConfig;
use Illuminate\Support\Facades\Artisan;
use Native\Laravel\Contracts\ProvidesPhpIni;
use Native\Laravel\Facades\Window;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        // Ensure the database file exists
        $dbPath = database_path('database.sqlite');
        if (!file_exists($dbPath)) {
            touch($dbPath);
        }

        $isFirstRun = true;
        try {
            if (Schema::hasTable('phppos_app_config')) {
                $setupComplete = PhpposAppConfig::where('key', 'setup_complete')->first();
                if ($setupComplete) {
                    $isFirstRun = false;
                }
            }
        } catch (\Exception $e) {
            // Table probably doesn't exist yet, so it's the first run
        }


        if ($isFirstRun && app()->isProduction()) {
            Window::open('setup')
                ->title('Setup')
                ->width(800)
                ->height(600)
                ->route('setup');
        } else {
            Window::open()
                ->width(1200)
                ->height(800);
        }
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
