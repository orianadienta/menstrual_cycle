<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingStatus extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'pause_reason',
        'paused_at',
        'resumed_at',
        'notes',
    ];

    protected $casts = [
        'paused_at' => 'date',
        'resumed_at' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope untuk cek status aktif
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }
}