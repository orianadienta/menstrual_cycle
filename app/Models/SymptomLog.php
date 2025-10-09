<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SymptomLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cycle_id',
        'symptom_id',
        'log_date',
    ];

    public function symptom()
    {
        return $this->belongsTo(Symptom::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
