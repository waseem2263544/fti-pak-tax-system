<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'contact_no', 'status', 'notes',
        'fbr_username', 'fbr_password', 'it_pin_code',
        'kpra_username', 'kpra_password', 'kpra_pin',
        'secp_password', 'secp_pin', 'folder_link'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all shareholders (clients who are shareholders of this client).
     */
    public function shareholders()
    {
        return $this->belongsToMany(
            Client::class,
            'shareholders',
            'client_id',
            'shareholder_client_id'
        )->withPivot('share_percentage');
    }

    /**
     * Get all clients where this client is a shareholder.
     */
    public function shareHolderIn()
    {
        return $this->belongsToMany(
            Client::class,
            'shareholders',
            'shareholder_client_id',
            'client_id'
        )->withPivot('share_percentage');
    }

    /**
     * Get all active services for this client.
     */
    public function activeServices()
    {
        return $this->belongsToMany(Service::class, 'client_services')
            ->withTimestamps();
    }

    /**
     * Get all tasks related to this client.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get all FBR notices for this client.
     */
    public function fbrNotices()
    {
        return $this->hasMany(FbrNotice::class);
    }

    /**
     * Get all reminders for this client.
     */
    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    public function secpDirectors() { return $this->hasMany(SecpDirector::class); }
    public function salesInvoices() { return $this->hasMany(AccSalesInvoice::class); }
    public function receipts() { return $this->hasMany(AccVoucher::class)->where('type', 'receipt'); }

    /**
     * Encrypt password when setting.
     */
    public function setFbrPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['fbr_password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt password when getting.
     */
    public function getFbrPasswordAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        return null;
    }

    public function setKpraPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['kpra_password'] = Crypt::encryptString($value);
        }
    }

    public function getKpraPasswordAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        return null;
    }

    public function salesInvoices() { return $this->hasMany(AccSalesInvoice::class); }
    public function receipts() { return $this->hasMany(AccVoucher::class)->where('type', 'receipt'); }

    public function getSharePointUrlAttribute()
    {
        $link = $this->folder_link;
        if (empty($link)) return null;

        // Already a full URL
        if (str_starts_with($link, 'http')) return $link;

        // Relative path like /sites/FairTaxInternational723/Shared Documents/...
        $encoded = rawurlencode($link);
        $encoded = str_replace('%2F', '/', $encoded);
        return 'https://fairtaxinternational.sharepoint.com/sites/FairTaxInternational723/Shared%20Documents/Forms/AllItems.aspx?id=' . $encoded;
    }

    public function setSecpPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['secp_password'] = Crypt::encryptString($value);
        }
    }

    public function getSecpPasswordAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        return null;
    }
}
