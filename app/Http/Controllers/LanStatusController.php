<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\TransferQueue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip' => ['required', 'string', 'max:45'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
        ]);

        Location::create([
            'name' => $data['name'],
            'ip' => $data['ip'],
            'port' => $data['port'] ?: null,
            'is_self' => false,
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

        $host = gethostname();
        $name = $host ?: 'unnamed';

        $self = Location::where('is_self', true)->first();
        if ($self) {
            $self->ip = $ip;
            $self->name = $name;
            $self->save();
        } else {
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
            $env = $this->setEnvValue($env, 'APP_NODE_NAME', $name);
            file_put_contents($envPath, $env);
        }

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
        $line = $key . '=' . $value;
        $pattern = '/^' . preg_quote($key, '/') . '=.*/m';

        if (preg_match($pattern, $contents) === 1) {
            return (string) preg_replace($pattern, $line, $contents);
        }

        return rtrim($contents) . PHP_EOL . $line . PHP_EOL;
    }
}
