<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'client_id',
        'created_by', 'status', 'due_date', 'priority'
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    /**
     * Get the user who created this task.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the client associated with this task.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get all users assigned to this task.
     */
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'task_user');
    }

    /**
     * Get notifications for this task.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'related_task_id');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable')->orderBy('created_at', 'desc');
    }
}
