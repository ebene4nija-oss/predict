<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpertPick extends Model
{
    use HasFactory;

    protected $fillable = [
        'expert_id',
        'match_id',
        'market',
        'pick',
        'rationale',
        'confidence',
    ];

    protected $casts = [
        'confidence' => 'float',
    ];

    public function expert(): BelongsTo
    {
        return $this->belongsTo(Expert::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }
}
