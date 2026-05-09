<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposAppConfig extends Model
{
    protected $table = 'phppos_app_config';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['key', 'value'];

    public static function get_key($key)
    {
        $config = self::find($key);
        return $config ? $config->value : null;
    }

    public static function batch_save($data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }
            self::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }
        return true;
    }
}
