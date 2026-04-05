<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileNumber extends Model
{
    protected $fillable = ['file_no', 'client_id', 'description'];

    public function client() { return $this->belongsTo(Client::class); }

    public static function nextNumber()
    {
        $last = self::max('file_no') ?? 0;
        return $last + 1;
    }
}
