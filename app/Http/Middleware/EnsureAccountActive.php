<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Suspended/locked accounts are logged out and blocked. */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            $reason = $user->isLocked() ? 'temporarily locked' : 'suspended';

            if ($request->is('api/*') || $request->expectsJson()) {
                $user->tokens()->delete();

                return response()->json([
                    'message' => "Your account has been $reason. Contact the city council for assistance.",
                    'error' => $user->isLocked() ? 'account_locked' : 'account_suspended',
                ], 403);
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account has been '.$reason.'. Contact the city council for assistance.',
            ]);
        }

        return $next($request);
    }
}
