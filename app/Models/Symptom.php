<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Symptom extends Model
{
    use HasFactory;

    protected $fillable = [
        'symptom_name',
        'category',
    ];

    public function symptom_logs()
    {
        return $this->hasMany(SymptomLog::class);
    }
}
