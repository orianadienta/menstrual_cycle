<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable 
// implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;
    // MustVerifyEmail;
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

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

    // helper untuk cek apakah user sudah mengisi cycle profile
    public function hasCompletedCycleHealthSetup(): bool
    {
        return $this->cycleProfile()->exists();
    }   

}

