<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:bind-identity', function () {
    $host = gethostname();
    $ip = gethostbyname($host);

    if ($ip === $host || str_starts_with($ip, '127.')) {
        $raw = trim((string) shell_exec('hostname -I'));
        if ($raw !== '') {
            $parts = preg_split('/\s+/', $raw);
            $ip = $parts[0] ?? $ip;
        }
    }

    $name = $host ?: 'unnamed';

    $envPath = base_path('.env');
    if (!file_exists($envPath)) {
        $this->error('.env file not found.');
        return;
    }

    $env = file_get_contents($envPath);
    $env = appBindIdentitySetEnv($env, 'APP_NODE_IP', $ip);
    $env = appBindIdentitySetEnv($env, 'APP_NODE_NAME', $name);
    file_put_contents($envPath, $env);

    $this->info("Bound identity: {$name} ({$ip})");
})->purpose('Bind the LAN node identity to the .env file');

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
