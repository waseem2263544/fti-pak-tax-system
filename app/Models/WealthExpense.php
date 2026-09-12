<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One Annex-F personal expense head for a client and year. */
class WealthExpense extends Model
{
    protected $fillable = ['client_id', 'tax_year', 'code', 'amount'];

    protected $casts = ['tax_year' => 'integer'];
}
