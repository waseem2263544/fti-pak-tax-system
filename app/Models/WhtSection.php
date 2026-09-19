<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Global FBR payment section. Not scoped to a company — these are statutory.
 */
class WhtSection extends Model
{
    protected $table = 'wht_sections';
    protected $fillable = ['section', 'payment_nature', 'payment_section', 'code', 'applies_to', 'regime', 'is_active'];

    /** FBR's payment page splits by regime before anything else. */
    public const REGIMES = [
        'adjustable' => 'Adjustable Income Tax',
        'final'      => 'Fixed / Final Income Tax',
    ];
    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($q) { return $q->where('is_active', true); }

    public function scopeFor($q, string $context)
    {
        return $q->whereIn('applies_to', [$context, 'both']);
    }

    public function getLabelAttribute(): string
    {
        return "[{$this->code}] {$this->section} - {$this->payment_nature}";
    }
}
