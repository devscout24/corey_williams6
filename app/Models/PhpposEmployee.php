<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

class PhpposEmployee extends Authenticatable
{
    use HasFactory;

    protected $table = 'phppos_employees';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'secret_key_2fa',
        'remember_token',
    ];

    public $timestamps = false;

    public function person(): BelongsTo
    {
        return $this->belongsTo(PhpposPerson::class, 'person_id', 'person_id');
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(PhpposLocation::class, 'phppos_employees_locations', 'employee_id', 'location_id');
    }

    public function getAuthIdentifierName(): string
    {
        return 'person_id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->person_id;
    }

    public static function findActiveByLogin(string $login): ?self
    {
        return static::query()
            ->where('deleted', 0)
            ->where('inactive', 0)
            ->where(function ($query) use ($login): void {
                $query->where('username', $login)
                    ->orWhere('employee_number', $login);
            })
            ->first();
    }

    public function canLoginNow(): bool
    {
        if (! $this->login_start_time || ! $this->login_end_time) {
            return true;
        }

        $now = now()->format('H:i:s');

        return $now >= $this->login_start_time && $now <= $this->login_end_time;
    }

    public function validatePassword(string $plainPassword): bool
    {
        $stored = (string) $this->password;

        if ($this->isLegacyMd5Hash($stored)) {
            $isValid = md5($plainPassword) === strtolower($stored);

            if ($isValid) {
                // Auto-upgrade legacy md5 password to Laravel hash on successful login.
                $this->password = Hash::make($plainPassword);
                $this->save();
            }

            return $isValid;
        }

        return Hash::check($plainPassword, $stored);
    }

    private function isLegacyMd5Hash(string $value): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}$/', strtolower($value));
    }

    /**
     * Check if the employee has a specific module permission
     */
    public function hasModulePermission(string $moduleId, string $actionId = ''): bool
    {
        if ($actionId !== '') {
            return \DB::table('phppos_permissions_actions')
                ->where('person_id', $this->person_id)
                ->where('module_id', $moduleId)
                ->where('action_id', $actionId)
                ->exists();
        }

        return \DB::table('phppos_permissions')
            ->where('person_id', $this->person_id)
            ->where('module_id', $moduleId)
            ->exists();
    }
}
