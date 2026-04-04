<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomatedTask extends Model
{
    protected $fillable = [
        'name', 'description', 'trigger_type', 'trigger_value',
        'service_id', 'task_template', 'priority', 'assign_to_roles',
        'is_active', 'last_run_at', 'next_run_at',
    ];

    protected $casts = [
        'assign_to_roles' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function service() { return $this->belongsTo(Service::class); }
}
