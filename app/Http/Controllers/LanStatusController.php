<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\TransferQueue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('lan.locations', compact('locations', 'transfers'));
    }

    public function resyncIp(Request $request): RedirectResponse
    {
        $self = Location::where('is_self', true)->first();
        if (!$self) {
            return redirect()->route('lan.locations')
                ->with('error', 'No self location found to resync.');
        }

        $ip = $this->resolveLanIp();
        if (!$ip) {
            return redirect()->route('lan.locations')
                ->with('error', 'Could not resolve a LAN IP address. Check network connectivity.');
        }

        $self->ip = $ip;
        $self->save();

        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $env = file_get_contents($envPath);
            $env = $this->setEnvValue($env, 'APP_NODE_IP', $ip);
            file_put_contents($envPath, $env);
        }

        return redirect()->route('lan.locations')
            ->with('status', "Self location IP re-synced to {$ip}.");
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
