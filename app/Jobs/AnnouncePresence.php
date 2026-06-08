<?php

namespace App\Jobs;

use App\Models\Location;
use App\Services\LanLocationRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class AnnouncePresence implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(LanLocationRegistry $registry): void
    {
        $payload = $registry->announcePayload();

        $locations = Location::where('is_self', false)
            ->whereNotNull('ip')
            ->whereNotNull('port')
            ->get();

        foreach ($locations as $location) {
            try {
                Http::timeout(10)
                    ->withHeaders($this->syncHeaders())
                    ->post($registry->urlFor($location).'/api/lan/announce', $payload);
            } catch (\Throwable $exception) {
                // Ignore LAN failures; user retries manually.
            }
        }
    }

    private function syncHeaders(): array
    {
        return [
            'X-Sync-Token' => (string) config('sync.shared_token'),
        ];
    }
}
