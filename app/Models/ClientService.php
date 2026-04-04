<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientService extends Model
{
    protected $table = 'client_services';

    protected $fillable = [
        'client_id', 'service_id', 'next_deadline', 'reminder_days',
    ];

    protected $casts = [
        'next_deadline' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
