<?php

namespace App\Http\Controllers;

use App\Jobs\SendItem;
use App\Models\Location;
use App\Models\TransferQueue;
use App\Services\LanLocationRegistry;
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
                $self->phpposLocation->update([
                    'name' => $data['name'],
                    'sync_url' => $self->ip && $self->port ? "http://{$self->ip}:{$self->port}" : $self->phpposLocation->sync_url,
                    'deleted' => false,
                ]);
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

    public function store(Request $request, LanLocationRegistry $registry): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip' => ['required', 'string', 'max:45'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
        ]);

        $registry->upsertPeer($data['ip'], (int) $data['port'], $data['name']);

        return redirect()->route('lan.locations')
            ->with('status', "Location {$data['name']} ({$data['ip']}) added.");
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ip' => ['required', 'string', 'max:45'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
        ]);

        $location->update([
            'name' => $data['name'],
            'ip' => $data['ip'],
            'port' => (int) $data['port'],
        ]);

        if ($location->phpposLocation) {
            $location->phpposLocation->update([
                'name' => $data['name'],
                'sync_url' => "http://{$data['ip']}:{$data['port']}",
                'deleted' => false,
            ]);
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

    public function poke(Location $location, LanLocationRegistry $registry): RedirectResponse
    {
        if ($location->is_self || !$location->ip || !$location->port) {
            return redirect()->route('lan.locations')
                ->with('error', 'Cannot poke the self location or a location without an IP and port.');
        }

        try {
            $response = Http::timeout(10)
                ->asJson()
                ->withHeaders($this->syncHeaders())
                ->post($registry->urlFor($location).'/api/lan/announce', $registry->announcePayload());

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

    public function retry(int $id): RedirectResponse
    {
        $transfer = TransferQueue::where('status', 'failed')->findOrFail($id);

        $transfer->update([
            'status' => 'pending',
            'error' => null,
        ]);

        SendItem::dispatch($transfer);

        return redirect()->route('lan.locations')
            ->with('status', "Transfer #{$id} queued for retry.");
    }

    public function resyncIpPreview(LanLocationRegistry $registry): \Illuminate\Http\JsonResponse
    {
        $ip = $registry->resolveLanIp();
        if (!$ip) {
            return response()->json(['error' => 'Could not resolve a LAN IP address. Check network connectivity.'], 422);
        }

        $configuredName = config('app.node_name');
        $host = gethostname();
        $name = $configuredName ?: ($host ?: 'unnamed');

        $existingSelf = Location::query()->where('is_self', true)->first();
        $port = (int) ($existingSelf?->port ?: config('app.node_port') ?: parse_url((string) config('app.url'), PHP_URL_PORT) ?: 8000);

        return response()->json([
            'ip'   => $ip,
            'port' => $port,
            'name' => $name,
        ]);
    }

    public function resyncIp(Request $request, LanLocationRegistry $registry): RedirectResponse
    {
        $data = $request->validate([
            'ip'   => ['required', 'string', 'max:45'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $ip   = $data['ip'];
        $port = (int) $data['port'];
        $name = $data['name'];

        $existingSelf = Location::query()->where('is_self', true)->first();
        $self = $registry->registerSelf($ip, $port, $name, $existingSelf?->phppos_location_id);

        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $env = file_get_contents($envPath);
            $env = $this->setEnvValue($env, 'APP_NODE_IP', $ip);
            $env = $this->setEnvValue($env, 'APP_NODE_NAME', $name);
            file_put_contents($envPath, $env);
        }

        Artisan::call('config:clear');

        $msg = $existingSelf ? "Self location IP re-synced to {$ip}:{$port}." : "Self location created with IP {$ip}:{$port}.";

        return redirect()->route('lan.locations')
            ->with('status', $msg);
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

    private function syncHeaders(): array
    {
        return [
            'X-Sync-Token' => (string) config('sync.shared_token'),
        ];
    }
}
