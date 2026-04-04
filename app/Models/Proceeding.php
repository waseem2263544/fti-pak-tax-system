<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proceeding extends Model
{
    protected $fillable = [
        'client_id', 'title', 'description', 'stage', 'case_number',
        'tax_year', 'section', 'hearing_date', 'order_date',
        'status', 'outcome', 'notes', 'assigned_to',
    ];

    protected $casts = [
        'hearing_date' => 'date',
        'order_date' => 'date',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to'); }
}
