<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a pipeline run transcript.
 *
 * Append-only, so there is no updated_at: the polling endpoint asks for
 * everything above the last id it saw and nothing is ever rewritten.
 */
class PipelineRunLine extends Model
{
    use HasFactory;

    public const LEVEL_STEP = 'step';
    public const LEVEL_INFO = 'info';
    public const LEVEL_SUCCESS = 'success';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';

    public $timestamps = false;

    protected $fillable = [
        'pipeline_run_id',
        'level',
        'message',
        'elapsed_ms',
        'created_at',
    ];

    protected $casts = [
        'elapsed_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PipelineRun::class, 'pipeline_run_id');
    }
}
