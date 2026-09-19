<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A selection of entries sent to IRIS, waiting for its PSID.
 *
 * While one is open its entries are spoken for: they cannot be put into
 * another request. That is the whole point - creating a PSID is quick enough
 * to do twice by accident, and FBR will happily issue two.
 */
class WhtPsidRequest extends Model
{
    protected $fillable = [
        'token', 'wht_company_id', 'kind', 'entry_ids', 'entry_count',
        'total_tax', 'status', 'psid_no', 'created_by', 'completed_at',
    ];

    protected $casts = [
        'entry_ids'    => 'array',
        'completed_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(WhtCompany::class, 'wht_company_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Entry ids already spoken for by an open request, for one agent and kind. */
    public static function lockedIds(int $companyId, string $kind, ?int $exceptId = null): array
    {
        return static::where('wht_company_id', $companyId)
            ->where('kind', $kind)
            ->where('status', 'open')
            ->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
            ->get()
            ->flatMap(fn($r) => $r->entry_ids)
            ->unique()
            ->values()
            ->all();
    }
}
