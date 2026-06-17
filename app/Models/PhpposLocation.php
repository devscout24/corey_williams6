<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class PhpposLocation extends Model
{
    use HasFactory;

    protected $table = 'phppos_locations';

    protected $primaryKey = 'location_id';

    protected $guarded = [];

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(PhpposEmployee::class, 'phppos_employees_locations', 'location_id', 'employee_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $location) {
            if (empty($location->ulid)) {
                $location->ulid = (string) Str::ulid();
            }
            static::generateSlug($location);
        });

        static::updating(function (self $location) {
            if ($location->isDirty('name')) {
                static::generateSlug($location);
            }
        });
    }

    private static function generateSlug(self $location): void
    {
        if ($location->name) {
            $base = Str::slug($location->name);
            $slug = $base;
            $suffix = 1;
            while (static::query()->where('slug', $slug)->when($location->exists, fn ($q) => $q->where('location_id', '!=', $location->location_id))->exists()) {
                $slug = $base.'-'.$suffix++;
            }
            $location->slug = $slug;
        }
    }
}
