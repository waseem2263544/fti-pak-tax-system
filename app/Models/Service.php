<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'display_name', 'description',
        'default_reminder_days', 'default_deadline_days'
    ];

    /**
     * Get all clients with this service.
     */
    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_services')
            ->withPivot('next_deadline', 'reminder_days')
            ->withTimestamps();
    }

    /**
     * Get all reminders for this service.
     */
    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }
}
