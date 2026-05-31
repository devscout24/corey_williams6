<?php

namespace App\Services;

use App\Models\PhpposAppConfig;
use Illuminate\Support\Facades\DB;

class AppConfigService
{
    public function exists(string $key): bool
    {
        return PhpposAppConfig::query()->where('key', $key)->exists();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $row = PhpposAppConfig::query()->find($key);

        return $row ? $row->value : $default;
    }

    public function getRaw(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }

    public function save(string $key, mixed $value): bool
    {
        $stored = $this->normalizeScalarConfigValue($value);

        return (bool) PhpposAppConfig::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $stored],
        );
    }

    public function delete(string $key): void
    {
        PhpposAppConfig::query()->where('key', $key)->delete();
    }

    public function batchSave(array $data): bool
    {
        DB::transaction(function () use ($data): void {
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    continue;
                }
                $this->save((string) $key, $value);
            }
        });

        return true;
    }

    public function getAdditionalPaymentTypes(): array
    {
        $paymentTypes = (string) $this->get('additional_payment_types', '');

        if ($paymentTypes === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $paymentTypes))));
    }

    public function getRawPhpposSessionExpiration(): ?int
    {
        $value = $this->get('phppos_session_expiration');

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    public function getRawNumberOfDecimals(): int
    {
        $value = $this->get('number_of_decimals');

        if (is_numeric($value)) {
            return (int) $value;
        }

        return 2;
    }

    private function normalizeScalarConfigValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
