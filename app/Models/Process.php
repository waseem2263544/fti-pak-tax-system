<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    protected $fillable = [
        'client_id', 'service_id', 'assigned_to', 'title',
        'description', 'stage', 'start_date', 'due_date',
        'completed_date', 'notes', 'template', 'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_date' => 'date',
        'metadata' => 'array',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function service() { return $this->belongsTo(Service::class); }
    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function comments() { return $this->morphMany(Comment::class, 'commentable')->orderBy('created_at', 'desc'); }
}
