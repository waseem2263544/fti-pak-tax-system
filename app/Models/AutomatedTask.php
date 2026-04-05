<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomatedTask extends Model
{
    protected $fillable = [
        'name', 'description', 'trigger_type', 'trigger_value',
        'service_id', 'task_template', 'priority', 'assign_to_user',
        'run_at_time', 'due_in_days', 'run_months', 'is_active', 'last_run_at', 'next_run_at',
    ];

    protected $casts = [
        'run_months' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function service() { return $this->belongsTo(Service::class); }
    public function assignedUser() { return $this->belongsTo(User::class, 'assign_to_user'); }

    public function shouldRunToday()
    {
        $today = now();

        // Check if restricted to specific months
        if (!empty($this->run_months)) {
            $months = is_array($this->run_months) ? $this->run_months : json_decode($this->run_months, true);
            if (is_array($months) && count($months) > 0) {
                if (!in_array((int) $today->month, $months)) {
                    return false;
                }
            }
        }

        switch ($this->trigger_type) {
            case 'monthly':
                $targetDay = (int) $this->trigger_value;
                $lastDay = (int) $today->daysInMonth;
                if ($targetDay > $lastDay) {
                    return (int) $today->day === $lastDay;
                }
                return (int) $today->day === $targetDay;

            case 'quarterly':
                // trigger_value = day of month, runs in months 1,4,7,10
                $quarterMonths = [1, 4, 7, 10];
                if (!in_array((int) $today->month, $quarterMonths)) {
                    return false;
                }
                $targetDay = (int) $this->trigger_value;
                $lastDay = (int) $today->daysInMonth;
                if ($targetDay > $lastDay) return (int) $today->day === $lastDay;
                return (int) $today->day === $targetDay;

            case 'yearly':
                return $today->format('m-d') === $this->trigger_value;

            case 'weekly':
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
