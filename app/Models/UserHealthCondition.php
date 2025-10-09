<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHealthCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'health_condition_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function healthCondition()
    {
        return $this->belongsTo(HealthCondition::class);
    }
}
