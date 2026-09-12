<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileNumber extends Model
{
    protected $fillable = ['file_no', 'client_id', 'client_name', 'description', 'source'];

    public function client() { return $this->belongsTo(Client::class); }

    /**
     * Who the file is for.
     *
     * Most of the imported register is one-off matters rather than clients on
     * the books, so a row may carry only the name the register recorded.
     */
    public function getPartyNameAttribute(): string
    {
        return $this->client?->name ?? ($this->client_name ?: '—');
    }

    public static function nextNumber()
    {
        $last = self::max('file_no') ?? 0;
        return $last + 1;
    }
}
