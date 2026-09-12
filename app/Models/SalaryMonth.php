<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryMonth extends Model
{
    public $timestamps = false;

    protected $fillable = ['salary_component_id', 'month', 'amount'];

    protected $casts = ['month' => 'integer'];
}
