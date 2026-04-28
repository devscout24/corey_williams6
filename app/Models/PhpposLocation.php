<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
                $location->ulid = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }
}
