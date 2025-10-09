<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Builder\Use_;

class Recommendations extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'category',
        'priority',
        'generated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
