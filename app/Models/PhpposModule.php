<?php

namespace App\Models;

use App\Models\PhpposModuleSubmodule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhpposModule extends Model
{
    use HasFactory;

    protected $table = 'phppos_modules';

    protected $primaryKey = 'module_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    public function submodules(): HasMany
    {
        return $this->hasMany(PhpposModuleSubmodule::class, 'module_id', 'module_id')
            ->where('enabled', true)
            ->orderBy('sort');
    }
}
