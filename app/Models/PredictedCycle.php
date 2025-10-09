<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class PredictedCycle extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'user_id',
        'predicted_start_date',
        'predicted_end_date',
        'fertile_window_start',
        'fertile_window_end',
        'ovulation_date',
        'generated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
