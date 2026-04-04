<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function roles()
    {
        return $this->belongsToMany(\App\Models\Role::class, 'role_user');
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles()->where('name', $role)->exists();
        }
        return $this->roles()->where('id', $role)->exists();
    }

    public function tasks()
    {
        return $this->belongsToMany(\App\Models\Task::class, 'task_user');
    }

    public function notifications()
    {
        return $this->hasMany(\App\Models\Notification::class);
    }

    public function unreadNotifications()
    {
        return $this->notifications()->where('is_read', false);
    }

    public function microsoftEmailSettings()
    {
        return $this->hasOne(\App\Models\MicrosoftEmailSettings::class);
    }
}
