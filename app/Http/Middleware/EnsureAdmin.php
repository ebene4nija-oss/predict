<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Admin authorization required.'], 403);
            }
            return redirect()->route('home')->with('warning', 'Unauthorized access to Admin Parameter Control Portal.');
        }

        return $next($request);
    }
}
