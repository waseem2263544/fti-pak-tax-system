<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'service_id', 'deadline_date',
        'reminder_type', 'email_sent', 'in_app_notified',
        'escalated', 'escalated_to'
    ];

    protected $casts = [
        'deadline_date' => 'date',
        'email_sent' => 'boolean',
        'in_app_notified' => 'boolean',
        'escalated' => 'boolean',
    ];

    /**
     * Get the client.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the service.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the user escalated to.
     */
    public function escalatedToUser()
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    /**
     * Get associated notifications.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'related_reminder_id');
    }
}
