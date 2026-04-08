<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SecpDirector extends Model
{
    protected $fillable = ['client_id', 'director_name', 'cnic', 'secp_password', 'secp_pin'];

    public function client() { return $this->belongsTo(Client::class); }

    public function setSecpPasswordAttribute($value)
    {
        $this->attributes['secp_password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getSecpPasswordAttribute($value)
    {
        if (!$value) return null;
        try { return Crypt::decryptString($value); } catch (\Exception $e) { return $value; }
    }
}
