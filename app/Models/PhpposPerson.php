<?php

namespace App\Models;

use App\Models\PhpposEmployee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PhpposPerson extends Model
{
    use HasFactory;

    protected $table = 'phppos_people';

    protected $primaryKey = 'person_id';

    public $timestamps = false;

    protected $guarded = [];

    public function employee(): HasOne
    {
        return $this->hasOne(PhpposEmployee::class, 'person_id', 'person_id');
    }

    public function customer(): HasOne
    {
        return $this->hasOne(PhpposCustomer::class, 'person_id', 'person_id');
    }
}
