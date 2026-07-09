<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItReturnTracker extends Model
{
    protected $table = 'it_return_trackers';
    protected $fillable = ['client_id', 'status', 'assigned_to', 'remarks', 'updated_by'];

    /** Status value => human label (order = workflow order). */
    public const STATUSES = [
        'not_yet_contacted'  => 'Not yet contacted',
        'documents_requested'=> 'Documents requested',
        'working'            => 'Working',
        'sent_for_review'    => 'Sent for Review',
        'filed'              => 'Filed',
        'not_required'       => 'Not required',
    ];

    public const DEFAULT_STATUS = 'not_yet_contacted';

    public function client() { return $this->belongsTo(Client::class); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
}
