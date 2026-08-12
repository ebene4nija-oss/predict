<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'market',
        'pick',
        'probability',
        'is_top10',
        'is_ai5',
        'rationale',
    ];

    protected $casts = [
        'probability' => 'float',
        'is_top10' => 'boolean',
        'is_ai5' => 'boolean',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }
}
