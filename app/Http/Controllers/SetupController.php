<?php

namespace App\Http\Controllers;

use App\Models\PhpposEmployee;
use App\Models\PhpposPerson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('login');
        }

        return view('setup.index');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'storage_path' => ['nullable', 'string', 'max:255'],
            'node_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return redirect()->back()->with('error', '.env file not found.');
        }

        $env = file_get_contents($envPath);
        $env = $this->setEnvValue($env, 'APP_NAME', $data['app_name']);
        $env = $this->setEnvValue($env, 'APP_NODE_NAME', $data['node_name']);

        if (!empty($data['storage_path'])) {
            $env = $this->setEnvValue($env, 'LARAVEL_STORAGE_PATH', $data['storage_path']);
        }

        $nodeIp = $this->resolveLanIp();
        if ($nodeIp) {
            $env = $this->setEnvValue($env, 'APP_NODE_IP', $nodeIp);
        }

        file_put_contents($envPath, $env);

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);

        DB::transaction(function () use ($data): void {
            $nameParts = preg_split('/\s+/', trim($data['full_name']));
            $firstName = $nameParts[0] ?? '';
            $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

            $person = PhpposPerson::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'create_date' => now(),
                'last_modified' => now(),
            ]);

            PhpposEmployee::create([
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
                'person_id' => $person->person_id,
                'deleted' => 0,
                'inactive' => 0,
            ]);

            $locationId = DB::table('phppos_locations')->value('location_id');
            if ($locationId) {
                DB::table('phppos_employees_locations')->updateOrInsert([
                    'employee_id' => $person->person_id,
                    'location_id' => $locationId,
                ], []);
            }
        });

        $this->markInstalled();

        return redirect()->route('login')->with('status', 'Setup completed. Please log in.');
    }

    private function resolveLanIp(): ?string
    {
        $host = gethostname();
        if (!$host) {
            return null;
        }

        $ip = gethostbyname($host);
        if ($ip && $ip !== $host && !str_starts_with($ip, '127.')) {
            return $ip;
        }

        $raw = trim((string) shell_exec('hostname -I'));
        if ($raw !== '') {
            $parts = preg_split('/\s+/', $raw);
            $candidate = $parts[0] ?? null;
            if ($candidate) {
                return $candidate;
            }
        }

        return $ip && $ip !== $host ? $ip : null;
    }

    private function setEnvValue(string $contents, string $key, string $value): string
    {
        $line = $key.'='.$value;
        $pattern = '/^'.preg_quote($key, '/').'=.*/m';

        if (preg_match($pattern, $contents) === 1) {
            return (string) preg_replace($pattern, $line, $contents);
        }

        return rtrim($contents).PHP_EOL.$line.PHP_EOL;
    }

    private function isInstalled(): bool
    {
        return file_exists(storage_path('app/install.lock'));
    }

    private function markInstalled(): void
    {
        $lockPath = storage_path('app/install.lock');
        if (!file_exists(dirname($lockPath))) {
            mkdir(dirname($lockPath), 0755, true);
        }

        file_put_contents($lockPath, (string) now());
    }
}
