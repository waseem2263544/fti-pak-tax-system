<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FbrNotice extends Model
{
    use HasFactory;

    protected $table = 'fbr_notices';
    protected $fillable = [
        'client_id', 'email_message_id', 'subject', 'body',
        'notice_section', 'tax_year', 'notice_date', 'email_received_at',
        'status', 'sender_email', 'raw_content', 'is_escalated', 'escalated_to'
    ];

    protected $casts = [
        'notice_date' => 'date',
        'email_received_at' => 'date',
        'is_escalated' => 'boolean',
    ];

    /**
     * Get the client associated with this notice.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user this notice is escalated to.
     */
    public function escalatedTo()
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    /**
     * Get associated notifications.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'related_fbr_notice_id');
    }

    /**
     * Scope: Get only new notices.
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope: Get only escalated notices.
     */
    public function scopeEscalated($query)
    {
        return $query->where('is_escalated', true);
    }
}
