<?php

namespace App\Jobs;

use App\Models\Location;
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

    public function handle(): void
    {
        $nodeIp = config('app.node_ip');
        $nodeName = config('app.node_name');

        $self = Location::where('is_self', true)->first();
        if (!$self && $nodeIp) {
            $self = Location::firstOrCreate(
                ['ip' => $nodeIp],
                ['name' => $nodeName, 'is_self' => true]
            );
        }

        if ($self) {
            $self->last_seen_at = now();
            $self->name = $nodeName;
            $self->ip = $nodeIp;
            $self->save();
        }

        $locations = Location::where('is_self', false)
            ->whereNotNull('ip')
            ->get();

        foreach ($locations as $location) {
            try {
                Http::timeout(3)->post("http://{$location->ip}/api/lan/announce", [
                    'ip' => $nodeIp,
                    'name' => $nodeName,
                ]);
            } catch (\Throwable $exception) {
                // Ignore LAN failures; user retries manually.
            }
        }
    }
}
