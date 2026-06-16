<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccAuditLog extends Model
{
    protected $table = 'acc_audit_logs';
    public $timestamps = false;

    protected $fillable = ['user_id', 'user_name', 'action', 'model_type', 'model_id', 'label', 'changes', 'created_at'];
    protected $casts = ['changes' => 'array', 'created_at' => 'datetime'];
}
