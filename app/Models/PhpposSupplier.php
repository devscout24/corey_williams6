<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposSupplier extends Model
{
    protected $table = 'phppos_suppliers';
    protected $primaryKey = 'person_id';
    public $incrementing = false;

    protected $fillable = [
        'person_id',
        'company_name',
        'account_number',
        'override_default_tax',
        'tax_class_id',
        'balance',
        'default_term_id',
        'image_id',
        'internal_notes',
        'custom_field_1_value',
        'custom_field_2_value',
        'custom_field_3_value',
        'custom_field_4_value',
        'custom_field_5_value',
        'custom_field_6_value',
        'custom_field_7_value',
        'custom_field_8_value',
        'custom_field_9_value',
        'custom_field_10_value',
        'deleted',
    ];

    public function person()
    {
        return $this->belongsTo(PhpposPerson::class, 'person_id', 'person_id');
    }

    public function taxes()
    {
        return $this->hasMany(PhpposSupplierTax::class, 'supplier_id', 'person_id');
    }

    public function files()
    {
        return $this->hasMany(PhpposPeopleFile::class, 'person_id', 'person_id');
    }
}
