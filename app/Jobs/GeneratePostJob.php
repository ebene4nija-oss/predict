<?php

namespace App\Jobs;

use App\Services\PostGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Writing an article is a multi-second model call, well past what an admin
 * request should hold open, so the button dispatches this instead.
 */
class GeneratePostJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $category = 'news',
        public ?string $brief = null,
        public ?int $authorId = null,
    ) {}

    public function handle(PostGenerationService $posts): void
    {
        $post = $posts->generate($this->category, $this->brief, $this->authorId);

        if ($post === null) {
            Log::warning('AI article generation produced nothing', ['category' => $this->category]);

            return;
        }

        Log::info('AI article generated', [
            'post_id' => $post->id,
            'status' => $post->status,
            'category' => $post->category,
        ]);
    }
}
