<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientFile extends Model
{
    protected $fillable = [
        'client_id', 'uploaded_by', 'filename', 'original_name',
        'mime_type', 'size', 'category', 'notes',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function uploadedBy() { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function sizeFormatted()
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
