<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class MicrosoftEmailSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'client_id', 'client_secret', 'access_token',
        'refresh_token', 'token_expires_at', 'email_address',
        'fbr_sender_email', 'last_synced_at'
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Encrypt client_secret when setting.
     */
    public function setClientSecretAttribute($value)
    {
        if ($value) {
            $this->attributes['client_secret'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt client_secret when getting.
     */
    public function getClientSecretAttribute($value)
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

    /**
     * Encrypt access_token when setting.
     */
    public function setAccessTokenAttribute($value)
    {
        if ($value) {
            $this->attributes['access_token'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt access_token when getting.
     */
    public function getAccessTokenAttribute($value)
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

    /**
     * Encrypt refresh_token when setting.
     */
    public function setRefreshTokenAttribute($value)
    {
        if ($value) {
            $this->attributes['refresh_token'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt refresh_token when getting.
     */
    public function getRefreshTokenAttribute($value)
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

    /**
     * Check if token is expired.
     */
    public function isTokenExpired()
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }
}
