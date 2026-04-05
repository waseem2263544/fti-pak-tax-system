<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomatedTask extends Model
{
    protected $fillable = [
        'name', 'description', 'trigger_type', 'trigger_value',
        'service_id', 'task_template', 'priority', 'assign_to_user',
        'run_at_time', 'due_in_days', 'is_active', 'last_run_at', 'next_run_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function service() { return $this->belongsTo(Service::class); }
    public function assignedUser() { return $this->belongsTo(User::class, 'assign_to_user'); }

    public function shouldRunToday()
    {
        $today = now();

        switch ($this->trigger_type) {
            case 'monthly':
                // trigger_value = day of month (1-31)
                $targetDay = (int) $this->trigger_value;
                $lastDay = (int) $today->daysInMonth;
                // If target day exceeds month's days, run on last day
                if ($targetDay > $lastDay) {
                    return (int) $today->day === $lastDay;
                }
                return (int) $today->day === $targetDay;

            case 'yearly':
                // trigger_value = "MM-DD" e.g. "09-30" for Sept 30
                return $today->format('m-d') === $this->trigger_value;

            case 'weekly':
                // trigger_value = day of week (0=Sun, 1=Mon...6=Sat)
                return (int) $today->dayOfWeek === (int) $this->trigger_value;

            case 'daily':
                return true;

            default:
                return false;
        }
    }

    public function hasRunToday()
    {
        return $this->last_run_at && $this->last_run_at->isToday();
    }
}
