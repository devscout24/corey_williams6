<?php

use App\Services\LanLocationRegistry;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:bind-identity', function () {
    $registry = app(LanLocationRegistry::class);
    $ip = $registry->resolveLanIp();
    if (! $ip) {
        $this->error('Could not resolve a non-loopback LAN IP address.');

        return;
    }

    $host = gethostname();
    $name = $host ?: 'unnamed';
    $slug = Str::slug($name);

    $envPath = base_path('.env');
    if (! file_exists($envPath)) {
        $this->error('.env file not found.');

        return;
    }

    $env = file_get_contents($envPath);
    $env = appBindIdentitySetEnv($env, 'APP_NODE_IP', $ip);
    $env = appBindIdentitySetEnv($env, 'APP_NODE_NAME', $slug);
    file_put_contents($envPath, $env);

    $this->info("Bound identity: {$name} ({$ip})");
})->purpose('Bind the LAN node identity to the .env file');

if (! function_exists('appBindIdentitySetEnv')) {
    function appBindIdentitySetEnv(string $contents, string $key, string $value): string
    {
        $line = $key.'='.$value;
        $pattern = '/^'.preg_quote($key, '/').'=.*/m';

        if (preg_match($pattern, $contents) === 1) {
            return (string) preg_replace($pattern, $line, $contents);
        }

        $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;

        return $contents;
    }
}
