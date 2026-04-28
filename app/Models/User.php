<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Check if the user has a specific module permission
     */
    public function hasModulePermission(string $module, string $action): bool
    {
        // Check phppos_permissions and phppos_permissions_actions tables
        // For now, check if user is admin or has explicit permission
        if ($action !== '') {
            return \DB::table('phppos_permissions_actions')
                ->where('person_id', $this->id)
                ->where('module_id', $module)
                ->where('action_id', $action)
                ->exists();
        }

        return \DB::table('phppos_permissions')
            ->where('person_id', $this->id)
            ->where('module_id', $module)
            ->exists();
    }
}
