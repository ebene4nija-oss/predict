<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'home_score',
        'away_score',
        'actual_outcome',
        'settled_at',
    ];

    protected $casts = [
        // Scores must be ints: the accuracy calculations compare them with ===
        // to detect draws, which silently fails when the driver hands back
        // numeric strings.
        'home_score' => 'integer',
        'away_score' => 'integer',
        'actual_outcome' => 'array',
        'settled_at' => 'datetime',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }
}
