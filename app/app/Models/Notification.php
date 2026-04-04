<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'client_id', 'title', 'message', 'type',
        'priority', 'is_read', 'read_at',
        'related_fbr_notice_id', 'related_reminder_id', 'related_task_id'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get related FBR notice.
     */
    public function relatedFbrNotice()
    {
        return $this->belongsTo(FbrNotice::class, 'related_fbr_notice_id');
    }

    /**
     * Get related reminder.
     */
    public function relatedReminder()
    {
        return $this->belongsTo(Reminder::class, 'related_reminder_id');
    }

    /**
     * Get related task.
     */
    public function relatedTask()
    {
        return $this->belongsTo(Task::class, 'related_task_id');
    }

    /**
     * Mark as read.
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Scope: Get only unread.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
