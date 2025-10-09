<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CycleProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'last_period_start',
        'initial_cycle_length',
        'initial_period_duration',
        'is_regular'
    ];

    protected $casts = [
    'last_period_start' => 'date',
    'is_regular' => 'boolean',
];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
