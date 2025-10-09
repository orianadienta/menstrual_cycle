<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'condition_name',
        'description',
    ];


    public function users()
    {
        return $this->belongsToMany(User::class, 'user_health_conditions');
    }
}
