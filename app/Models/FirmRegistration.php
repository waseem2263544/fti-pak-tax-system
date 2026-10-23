<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * A partnership firm being registered under the Partnership Act, 1932.
 *
 * partners:  list of {name, relation, father_name, cnic, address, cell, capital_share, profit_share}
 * witnesses: list of {name, relation, father_name, cnic, address, cell}
 * managing_partner: index into partners, or null when the deed names none
 */
class FirmRegistration extends Model
{
    protected $fillable = [
        'client_id', 'firm_name', 'principal_place', 'other_places', 'business_description',
        'deed_date', 'commencement_date', 'duration', 'partners', 'witnesses',
        'managing_partner', 'managing_powers', 'stamp_top_cm', 'notes', 'created_by',
    ];

    protected $casts = [
        'deed_date' => 'date',
        'commencement_date' => 'date',
        'partners' => 'array',
        'witnesses' => 'array',
        'managing_partner' => 'integer',
        'stamp_top_cm' => 'float',
    ];

    /** Default powers of a managing partner, one per line; editable per firm. */
    public const DEFAULT_MANAGING_POWERS = <<<'TXT'
To manage, supervise, and control the day-to-day business operations of the firm.
To represent the firm before FBR, banks, government departments, regulatory authorities, and other relevant institutions.
To apply for, obtain, renew, and maintain licenses, permits, registrations and other necessary approvals, subject to applicable laws and the approval of partners where required.
To negotiate and execute contracts, agreements, and business transactions on behalf of the firm within the scope of his authority.
To supervise employees, workers, contractors, and other persons engaged in the firm.
TXT;

    public const RELATIONS = ['S/O', 'D/O', 'W/O'];

    public function client() { return $this->belongsTo(Client::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function managingPartner(): ?array
    {
        return $this->managing_partner === null ? null : ($this->partners[$this->managing_partner] ?? null);
    }

    /** "Muhammad Saqib Bokhari S/O Syed Muhammad Zakria" */
    public static function fullName(array $person): string
    {
        $rel = $person['relation'] ?? 'S/O';
        return trim(($person['name'] ?? '') . ' ' . $rel . ' ' . ($person['father_name'] ?? ''));
    }

    /** 21 -> "21st" */
    public static function ordinal(int $n): string
    {
        $suffix = (in_array($n % 100, [11, 12, 13])) ? 'th' : (['th', 'st', 'nd', 'rd'][$n % 10] ?? 'th');
        return $n . $suffix;
    }

    /** Split "21st" into ["21", "st"] for superscript rendering. */
    public static function ordinalParts(Carbon $date): array
    {
        $o = self::ordinal($date->day);
        return [substr($o, 0, -2), substr($o, -2)];
    }
}
