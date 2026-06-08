<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\PhpposLocation;
use App\Models\TransferQueue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class LanStatusController extends Controller
{
    public function index(): View
    {
        $locations = Location::orderByDesc('is_self')
            ->orderBy('name')
            ->get();

        $transfers = TransferQueue::with('destination')
            ->latest()
            ->take(50)
            ->get();

        $appUrl = config('app.url');

        return view('lan.locations', compact('locations', 'transfers', 'appUrl'));
    }

    public function updateSelfName(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $self = Location::query()->where('is_self', true)->first();
        if ($self) {
            $self->name = $data['name'];
            $self->save();

            if ($self->phpposLocation) {
                $self->phpposLocation->update(['name' => $data['name']]);
            }
        }

        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $env = file_get_contents($envPath);
            $env = $this->setEnvValue($env, 'APP_NODE_NAME', $data['name']);
            file_put_contents($envPath, $env);
        }

        Artisan::call('config:clear');

        return redirect()->route('lan.locations')->with('status', 'Self location label updated.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip' => ['required', 'string', 'max:45'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ]);

        $phpposLocation = PhpposLocation::create([
            'name' => $data['name'],
        ]);

        Location::create([
            'name' => $data['name'],
            'ip' => $data['ip'],
            'port' => $data['port'] ?: null,
            'is_self' => false,
            'phppos_location_id' => $phpposLocation->location_id,
        ]);

        return redirect()->route('lan.locations')
            ->with('status', "Location {$data['name']} ({$data['ip']}) added.");
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip' => ['required', 'string', 'max:45'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ]);

        $location->update([
            'name' => $data['name'],
            'ip' => $data['ip'],
            'port' => $data['port'] ?: null,
        ]);

        if ($location->phpposLocation) {
            $location->phpposLocation->update(['name' => $data['name']]);
        }

        return redirect()->route('lan.locations')
            ->with('status', "Location {$data['name']} updated.");
    }

    public function destroy(Location $location): RedirectResponse
    {
        if ($location->is_self) {
            return redirect()->route('lan.locations')
                ->with('error', 'Cannot delete the self location.');
        }

        $name = $location->name;

        if ($location->phpposLocation) {
            $location->phpposLocation->update(['deleted' => 1]);
        }

        $location->delete();

        return redirect()->route('lan.locations')
            ->with('status', "Location {$name} deleted.");
    }

    public function poke(Location $location): RedirectResponse
    {
        if ($location->is_self || !$location->ip) {
            return redirect()->route('lan.locations')
                ->with('error', 'Cannot poke the self location or a location without an IP.');
        }

        $nodeIp = config('app.node_ip');
        $nodeName = config('app.node_name');

        try {
            $response = Http::timeout(5)->post("{$location->url}/api/lan/announce", [
                'ip' => $nodeIp,
                'name' => $nodeName,
            ]);

            if ($response->ok()) {
                return redirect()->route('lan.locations')
                    ->with('status', "Poke sent to {$location->name} at {$location->url} — host acknowledged.");
            }

            return redirect()->route('lan.locations')
                ->with('error', "Poke sent to {$location->name} at {$location->url} but target responded with {$response->status()}.");
        } catch (\Throwable $e) {
            return redirect()->route('lan.locations')
                ->with('error', "Could not reach {$location->name} at {$location->url}: {$e->getMessage()}");
        }
    }

    public function resyncIp(Request $request): RedirectResponse
    {
        $ip = $this->resolveLanIp();
        if (!$ip) {
            return redirect()->route('lan.locations')
                ->with('error', 'Could not resolve a LAN IP address. Check network connectivity.');
        }

        // Never overwrite the configured node name during IP resync.
        $configuredName = config('app.node_name');
        $host = gethostname();
        $name = $configuredName ?: ($host ?: 'unnamed');


        $self = Location::query()->where('is_self', true)->first();
        // dd($ip, $name, $self);
        if ($self) {
            $self->ip = $ip;
            $self->name = $name;
            $self->save();
        }
        else {
            Location::create([
                'ip' => $ip,
                'name' => $name,
                'is_self' => true,
                'last_seen_at' => now(),
            ]);
        }

        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $env = file_get_contents($envPath);
            $env = $this->setEnvValue($env, 'APP_NODE_IP', $ip);
            if (!$configuredName) {
                $env = $this->setEnvValue($env, 'APP_NODE_NAME', $name);
            }
            file_put_contents($envPath, $env);
        }

        Artisan::call('config:clear');

        $msg = $self ? "Self location IP re-synced to {$ip}." : "Self location created with IP {$ip}.";

        return redirect()->route('lan.locations')
            ->with('status', $msg);
    }

    private function resolveLanIp(): ?string
    {
        $host = gethostname();
        if (!$host) {
            return null;
        }

        $ip = gethostbyname($host);
        if ($this->isValidLanIp($ip)) {
            return $ip;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $raw = trim((string) shell_exec('powershell -NoProfile -Command "Get-NetIPAddress -AddressFamily IPv4 | Where-Object IPAddress -NotLike \'127.*\' | Select-Object -First 1 -ExpandProperty IPAddress" 2>NUL'));
            if ($this->isValidLanIp($raw)) {
                return $raw;
            }
        } else {
            $raw = trim((string) shell_exec('hostname -I 2>/dev/null'));
            if ($raw !== '') {
                $parts = preg_split('/\s+/', $raw);
                foreach ($parts as $candidate) {
                    if ($this->isValidLanIp($candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        // Fall back to the configured node IP if one was previously saved
        $configured = config('app.node_ip');
        if ($this->isValidLanIp($configured)) {
            return $configured;
        }

        return null;
    }

    private function isValidLanIp(?string $ip): bool
    {
        if ($ip === null || $ip === '' || $ip === gethostname()) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }

        // Reject loopback (127.x.x.x)
        if (str_starts_with($ip, '127.')) {
            return false;
        }

        return true;
    }

    private function setEnvValue(string $contents, string $key, string $value): string
    {
        $line = $key . '=' . $value;
        $pattern = '/^' . preg_quote($key, '/') . '=.*/m';

        if (preg_match($pattern, $contents) === 1) {
            return (string) preg_replace($pattern, $line, $contents);
        }

        return rtrim($contents) . PHP_EOL . $line . PHP_EOL;
    }
}
