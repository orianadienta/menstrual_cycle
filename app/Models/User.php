<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ===== RELATIONS =====

    public function cycles()
    {
        return $this->hasMany(Cycle::class);
    }

    public function cycleProfile()
    {
        return $this->hasOne(CycleProfile::class);
    }

    public function healthConditions()
    {
        return $this->belongsToMany(HealthCondition::class, 'user_health_conditions');
    }

    public function symptomsLogs()
    {
        return $this->hasMany(SymptomLog::class);
    }

    public function predictedCycles()
    {
        return $this->hasMany(PredictedCycle::class);
    }

    public function recommendations()
    {
        return $this->hasMany(Recommendations::class);
    }

    public function trackingStatus()
    {
        return $this->hasOne(TrackingStatus::class)->latest();
    }

    /**
     * Device tokens untuk FCM push notifications
     */
    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    // ===== HELPERS =====

    public function hasCompletedCycleHealthSetup(): bool
    {
        return $this->cycleProfile()->exists();
    }

    /**
     * Route notification untuk FCM channel
     * Return array of tokens untuk multicast ke multiple devices
     */
    public function routeNotificationForFcm()
    {
        return $this->deviceTokens()
            ->pluck('token')
            ->toArray();
    }

    /**
     * Helper untuk cek apakah user punya device tokens
     */
    public function hasRegisteredDevices(): bool
    {
        return $this->deviceTokens()->exists();
    }

    /**
     * Helper untuk cek apakah user boleh terima notifikasi
     */
    public function canReceiveNotifications(): bool
    {
        return $this->hasRegisteredDevices();
    }
}
