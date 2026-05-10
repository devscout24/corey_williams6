<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhpposRegisterLogAudit extends Model
{
    protected $table = 'phppos_register_log_audit';
    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(PhpposEmployee::class, 'employee_id', 'person_id');
    }

    public function log()
    {
        return $this->belongsTo(PhpposRegisterLog::class, 'register_log_id', 'register_log_id');
    }
}
