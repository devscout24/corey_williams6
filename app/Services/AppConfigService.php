<?php

namespace App\Services;

use App\Models\PhpposAppConfig;
use Illuminate\Support\Arr;
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
        return (bool) PhpposAppConfig::query()->updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value],
        );
    }

    public function delete(string $key): void
    {
        PhpposAppConfig::query()->where('key', $key)->delete();
    }

    public function batchSave(array $data): bool
    {
        if ($this->hasDuplicateTaxes($data)) {
            return false;
        }

        DB::transaction(function () use ($data): void {
            foreach ($data as $key => $value) {
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

    private function hasDuplicateTaxes(array $data): bool
    {
        if (! Arr::has($data, 'default_tax_1_name')) {
            return false;
        }

        $taxes = [];
        for ($i = 1; $i <= 5; $i++) {
            $name = (string) Arr::get($data, "default_tax_{$i}_name", '');
            $rate = (string) Arr::get($data, "default_tax_{$i}_rate", '');
            $taxes[$i] = $name.$rate;
        }

        for ($i = 1; $i <= 5; $i++) {
            if ($taxes[$i] === '') {
                continue;
            }

            for ($j = 1; $j <= 5; $j++) {
                if ($i === $j || $taxes[$j] === '') {
                    continue;
                }

                if ($taxes[$i] === $taxes[$j]) {
                    return true;
                }
            }
        }

        return false;
    }
}
