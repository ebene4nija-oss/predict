<?php

namespace App\Http\Middleware;

use App\Models\Pageview;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackPageviewsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track successful GET requests and ignore admin, static assets, or API webhooks
        if ($request->isMethod('GET') && $response->getStatusCode() === 200) {
            $path = $request->path();

            if (!str_starts_with($path, 'admin') && !str_starts_with($path, 'webhooks') && !str_starts_with($path, '_') && !str_contains($path, '.')) {
                $attributes = [
                    'url' => mb_substr($request->fullUrl(), 0, 500),
                    'path' => mb_substr($path === '/' ? '/' : '/' . $path, 0, 255),
                    'user_id' => $request->user()?->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => mb_substr($request->userAgent() ?? '', 0, 500),
                    'referer' => mb_substr($request->header('referer') ?? '', 0, 500),
                    'created_at' => now(),
                ];

                // Written after the response has been sent, so analytics never
                // sits on the critical path of a page load.
                app()->terminating(function () use ($attributes) {
                    try {
                        Pageview::create($attributes);
                    } catch (\Throwable $e) {
                        Log::debug('Pageview tracking failed', ['error' => $e->getMessage()]);
                    }
                });
            }
        }

        return $response;
    }
}
