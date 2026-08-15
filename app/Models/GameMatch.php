<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'external_id',
        'provider',
        'home_team',
        'away_team',
        'league',
        'kickoff_at',
        'status',
        'home_form',
        'away_form',
        'h2h_summary',
        'injury_notes',
        'preview_text',
    ];

    protected $casts = [
        'kickoff_at' => 'datetime',
        'home_form' => 'array',
        'away_form' => 'array',
    ];

    public function hasStarted(): bool
    {
        return $this->kickoff_at !== null && $this->kickoff_at->isPast();
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class, 'match_id');
    }

    public function expertPicks(): HasMany
    {
        return $this->hasMany(ExpertPick::class, 'match_id');
    }

    public function result(): HasOne
    {
        return $this->hasOne(Result::class, 'match_id');
    }
}
