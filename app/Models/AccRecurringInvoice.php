<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AccRecurringInvoice extends Model
{
    protected $table = 'acc_recurring_invoices';
    protected $fillable = [
        'client_id', 'frequency', 'next_date', 'due_days', 'reference', 'notes', 'terms',
        'discount_amount', 'items', 'is_active', 'last_generated_date', 'created_by',
    ];
    protected $casts = [
        'next_date' => 'date', 'last_generated_date' => 'date',
        'is_active' => 'boolean', 'items' => 'array',
    ];

    public function client() { return $this->belongsTo(Client::class); }

    public function isDue(): bool
    {
        return $this->is_active && $this->next_date && $this->next_date->lte(now()->startOfDay());
    }

    /** Move next_date forward by one period after generating. */
    public function advanceSchedule(): void
    {
        $next = Carbon::parse($this->next_date);
        $this->last_generated_date = $this->next_date;
        $this->next_date = (match ($this->frequency) {
            'weekly'    => $next->addWeek(),
            'quarterly' => $next->addMonthsNoOverflow(3),
            'yearly'    => $next->addYearNoOverflow(),
            default     => $next->addMonthNoOverflow(),
        })->toDateString();
        $this->save();
    }
}
