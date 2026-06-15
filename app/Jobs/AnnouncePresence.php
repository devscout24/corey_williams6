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
use Illuminate\Support\Facades\Log;

class AnnouncePresence implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(LanLocationRegistry $registry): void
    {
        $payload = $registry->announcePayload();
        $this->log('Announcing presence: '.json_encode($payload));

        $locations = Location::where('is_self', false)
            ->whereNotNull('ip')
            ->whereNotNull('port')
            ->get();

        $this->log('Found '.$locations->count().' peer(s) to announce to');

        foreach ($locations as $location) {
            try {
                $url = $registry->urlFor($location).'/api/lan/announce';
                $this->log('POST '.$location->name.' @ '.$url);
                Http::timeout(10)
                    ->asJson()
                    ->withHeaders($this->syncHeaders())
                    ->post($url, $payload);
            } catch (\Throwable $exception) {
                $this->log('Failed to announce to '.$location->name.': '.$exception->getMessage());
            }
        }

        $this->log('AnnouncePresence complete');
    }

    private function log(string $message): void
    {
        if (config('app.debug') || env('LAN_SYNC_DEBUG') === 'true') {
            Log::channel('lan_sync')->debug('[AnnouncePresence] '.$message);
        }
    }

    private function syncHeaders(): array
    {
        return [
            'X-Sync-Token' => (string) config('sync.shared_token'),
        ];
    }
}
