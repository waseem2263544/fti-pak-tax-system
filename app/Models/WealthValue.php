<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WealthValue extends Model
{
    protected $fillable = ['wealth_line_id', 'tax_year', 'amount'];

    protected $casts = ['tax_year' => 'integer'];

    public function line()
    {
        return $this->belongsTo(WealthLine::class, 'wealth_line_id');
    }
}
