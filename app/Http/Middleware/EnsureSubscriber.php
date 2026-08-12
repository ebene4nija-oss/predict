<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriber
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isSubscriber()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Subscription required.'], 403);
            }
            return redirect()->route('subscription.pricing')->with('warning', 'Please subscribe to access full Top 10 picks, AI Top 5, and Expert Analysis.');
        }

        return $next($request);
    }
}
