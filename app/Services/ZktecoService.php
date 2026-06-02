<?php

namespace App\Services;

use Rats\Zkteco\Lib\ZKTeco;

class ZktecoService
{
    const CONFIG_KEY_IP = 'zkteco_device_ip';
    const CONFIG_KEY_PORT = 'zkteco_device_port';

    public function __construct(
        protected AppConfigService $config,
    ) {}

    public function getIp(): string
    {
        return $this->config->get(self::CONFIG_KEY_IP, '192.168.1.201');
    }

    public function getPort(): int
    {
        return (int) $this->config->get(self::CONFIG_KEY_PORT, '4370');
    }

    public function saveConfig(string $ip, int $port): void
    {
        $this->config->save(self::CONFIG_KEY_IP, $ip);
        $this->config->save(self::CONFIG_KEY_PORT, (string) $port);
    }

    public function testConnection(): array
    {
        $device = $this->createDevice();
        try {
            if (!$device->connect()) {
                return ['success' => false, 'error' => 'Could not connect to device'];
            }

            $info = [
                'name' => $device->deviceName(),
                'serial' => $device->serialNumber(),
                'version' => $device->version(),
                'firmware' => $device->fmVersion(),
                'platform' => $device->platform(),
                'os' => $device->osVersion(),
                'device_time' => $device->getTime(),
            ];

            $device->disconnect();

            return ['success' => true, 'info' => $info];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAttendance(): array
    {
        $device = $this->createDevice();
        try {
            if (!$device->connect()) {
                return ['success' => false, 'error' => 'Could not connect to device'];
            }

            $device->disableDevice();
            $logs = $device->getAttendance();
            $device->enableDevice();
            $device->disconnect();

            $records = [];
            foreach (array_reverse($logs) as $log) {
                $records[] = [
                    'uid' => $log['uid'] ?? '',
                    'id' => $log['id'] ?? '',
                    'state' => $log['state'] ?? '',
                    'timestamp' => $log['timestamp'] ?? '',
                    'type' => $log['type'] ?? '',
                ];
            }

            return ['success' => true, 'records' => $records, 'count' => count($records)];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function createDevice(): ZKTeco
    {
        return new ZKTeco($this->getIp(), $this->getPort());
    }
}
