<?php

namespace App\Services;

use App\Models\GameMatch;

class PredictionService
{
    protected ClaudePredictionService $claudeService;

    public function __construct(?ClaudePredictionService $claudeService = null)
    {
        $this->claudeService = $claudeService ?? new ClaudePredictionService();
    }

    /**
     * Generate & store match predictions using Claude AI prediction engine.
     * Handled strictly by Claude AI service with Admin parameter controls.
     */
    public function calculateAndStore(GameMatch $match): array
    {
        return $this->claudeService->generatePredictions($match);
    }
}
