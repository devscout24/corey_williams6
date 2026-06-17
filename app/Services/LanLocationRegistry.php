<?php

namespace App\Services;

use App\Models\Location;
use App\Models\PhpposLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class LanLocationRegistry
{
    public function registerSelf(string $ip, int $port, ?string $name = null, ?int $phpposLocationId = null): Location
    {
        $ip = $this->normalizeIp($ip);
        $this->validatePort($port);

        return DB::transaction(function () use ($ip, $port, $name, $phpposLocationId): Location {
            $self = Location::query()->where('is_self', true)->first();
            $location = Location::query()->where('ip', $ip)->first();

            if ($location && $self && $location->getKey() !== $self->getKey()) {
                $self->is_self = false;
                $self->save();
                $self = $location;
            }

            $location = $self ?? $location ?? new Location;
            $name = $name ?: $location->name ?: config('app.node_name') ?: gethostname() ?: 'unnamed';
            $slug = $location->slug ?: Str::slug($name);

            $phpposLocation = $this->resolvePhpposLocation($phpposLocationId, $location, $name, $slug);
            $this->syncPhpposLocation($phpposLocation, $name, $slug, $ip, $port);

            Location::query()
                ->where('is_self', true)
                ->when($location->exists, fn ($query) => $query->whereKeyNot($location->getKey()))
                ->update(['is_self' => false]);

            $location->fill([
                'name' => $name,
                'slug' => $slug,
                'ip' => $ip,
                'port' => $port,
                'is_self' => true,
                'phppos_location_id' => $phpposLocation->location_id,
                'last_seen_at' => now(),
            ]);
            $location->save();

            return $location->fresh(['phpposLocation']) ?? $location;
        });
    }

    public function upsertPeer(string $ip, int $port, string $name, ?string $slug = null, ?string $ulid = null): Location
    {
        $ip = $this->normalizeIp($ip);
        $this->validatePort($port);

        return DB::transaction(function () use ($ip, $port, $name, $slug, $ulid): Location {
            $location = Location::query()->where('ip', $ip)->first() ?? new Location;

            $slug = $slug ?: ($location->slug ?: Str::slug($name));

            $phpposLocation = $location->phpposLocation;
            if (! $phpposLocation) {
                $phpposLocation = PhpposLocation::query()->where('slug', $slug)->first();
            }
            if (! $phpposLocation) {
                $phpposLocation = PhpposLocation::create(['name' => $name]);
            }

            $this->syncPhpposLocation($phpposLocation, $name, $slug, $ip, $port, $ulid);

            $location->fill([
                'name' => $name,
                'slug' => $slug,
                'ip' => $ip,
                'port' => $port,
                'is_self' => false,
                'phppos_location_id' => $phpposLocation->location_id,
                'last_seen_at' => now(),
            ]);
            $location->save();

            return $location->fresh(['phpposLocation']) ?? $location;
        });
    }

    public function selfOrFail(): Location
    {
        $self = Location::query()->where('is_self', true)->first();

        if (! $self || ! $self->ip || ! $self->port) {
            throw new RuntimeException('Self LAN location is not registered. Run php artisan app:register-self --ip=<LAN_IP> --port=<PORT> before syncing.');
        }

        $this->normalizeIp($self->ip);
        $this->validatePort((int) $self->port);

        return $self;
    }

    public function announcePayload(): array
    {
        $self = $this->selfOrFail();

        return [
            'ip' => $self->ip,
            'port' => (int) $self->port,
            'name' => $self->name,
            'slug' => $self->slug,
            'phppos_location_ulid' => $self->phpposLocation?->ulid,
        ];
    }

    public function urlFor(Location $location): string
    {
        if (! $location->ip || ! $location->port) {
            throw new RuntimeException("LAN location {$location->name} is missing an IP address or port.");
        }

        $ip = $this->normalizeIp($location->ip);
        $port = (int) $location->port;
        $this->validatePort($port);

        return "http://{$ip}:{$port}";
    }

    public function resolveLanIp(): ?string
    {
        $host = gethostname();
        if ($host) {
            $ip = gethostbyname($host);
            if ($this->isUsableLanIp($ip)) {
                return $ip;
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $raw = trim((string) shell_exec('powershell -NoProfile -Command "Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -notlike \'127.*\' -and $_.IPAddress -ne \'0.0.0.0\' } | Select-Object -First 1 -ExpandProperty IPAddress" 2>NUL'));
            if ($this->isUsableLanIp($raw)) {
                return $raw;
            }
        } else {
            $raw = trim((string) shell_exec('hostname -I 2>/dev/null'));
            if ($raw !== '') {
                foreach (preg_split('/\s+/', $raw) ?: [] as $candidate) {
                    if ($this->isUsableLanIp($candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        $configured = config('app.node_ip');

        return $this->isUsableLanIp($configured) ? $configured : null;
    }

    public function isUsableLanIp(?string $ip): bool
    {
        if (! is_string($ip) || $ip === '' || $ip === gethostname()) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return ! str_starts_with($ip, '127.')
            && $ip !== '::1'
            && $ip !== '0.0.0.0';
    }

    public function syncHeaders(): array
    {
        return [
            'X-Sync-Token' => (string) config('sync.shared_token'),
        ];
    }

    public function pokeBack(array $incomingData, string $pokeId): void
    {
        $payload = $this->announcePayload();
        $targetUrl = "http://{$incomingData['ip']}:{$incomingData['port']}/api/lan/announce";

        try {
            Http::timeout(10)
                ->asJson()
                ->withHeaders(array_merge($this->syncHeaders(), [
                    'X-Poke-Ack' => '1',
                    'X-Poke-Id' => $pokeId,
                ]))
                ->post($targetUrl, $payload);
        } catch (\Throwable) {
            // Poke-back is best-effort; the initiator can retry
        }
    }

    private function normalizeIp(string $ip): string
    {
        $ip = trim($ip);

        if (! $this->isUsableLanIp($ip)) {
            throw new InvalidArgumentException('A non-loopback LAN IP address is required.');
        }

        return $ip;
    }

    private function validatePort(int $port): void
    {
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('A valid LAN port between 1 and 65535 is required.');
        }
    }

    private function resolvePhpposLocation(?int $phpposLocationId, Location $location, string $name, string $slug): PhpposLocation
    {
        if ($phpposLocationId) {
            return PhpposLocation::query()->where('location_id', $phpposLocationId)->firstOrFail();
        }

        if ($location->phpposLocation) {
            return $location->phpposLocation;
        }

        $bySlug = PhpposLocation::query()->where('slug', $slug)->first();
        if ($bySlug) {
            return $bySlug;
        }

        $firstLocation = PhpposLocation::query()
            ->where('deleted', false)
            ->orderBy('location_id')
            ->first();

        return $firstLocation ?? PhpposLocation::create(['name' => $name]);
    }

    private function syncPhpposLocation(PhpposLocation $location, string $name, string $slug, string $ip, int $port, ?string $remoteUlid = null): void
    {
        if ($remoteUlid) {
            $location->ulid = $remoteUlid;
        } elseif (! $location->ulid) {
            $location->ulid = (string) Str::ulid();
        }

        $location->forceFill([
            'name' => $name,
            'slug' => $slug,
            'sync_url' => "http://{$ip}:{$port}",
            'deleted' => false,
        ])->save();
    }
}
