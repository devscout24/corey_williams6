<?php

namespace App\Console\Commands;

use App\Services\LanLocationRegistry;
use Illuminate\Console\Command;
use InvalidArgumentException;

class RegisterSelf extends Command
{
    protected $signature = 'app:register-self
        {--ip= : LAN IP address for this node}
        {--port= : HTTP port this node listens on}
        {--name= : Optional node name}
        {--phppos-location-id= : Existing phppos_locations.location_id to bind}';

    protected $description = 'Register or update the self LAN location before sync jobs run.';

    public function handle(LanLocationRegistry $registry): int
    {
        $ip = $this->option('ip') ?: $registry->resolveLanIp();
        $port = $this->option('port') ?: config('app.node_port') ?: parse_url((string) config('app.url'), PHP_URL_PORT);
        $name = $this->option('name') ?: config('app.node_name') ?: gethostname() ?: 'unnamed';
        $phpposLocationId = $this->option('phppos-location-id');

        if (! $ip) {
            $this->error('Could not resolve a non-loopback LAN IP address. Pass --ip=<LAN_IP>.');
            return self::FAILURE;
        }

        if (! $port) {
            $this->error('A LAN port is required. Pass --port=<PORT> or set APP_NODE_PORT.');
            return self::FAILURE;
        }

        try {
            $self = $registry->registerSelf(
                (string) $ip,
                (int) $port,
                (string) $name,
                $phpposLocationId ? (int) $phpposLocationId : null
            );
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info("Registered self LAN node {$self->name} at {$self->ip}:{$self->port}.");

        return self::SUCCESS;
    }
}
