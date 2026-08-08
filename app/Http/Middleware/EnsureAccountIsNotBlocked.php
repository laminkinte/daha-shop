<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocking an account is meant to take effect immediately, not just on the
 * next login - this force-logs-out anyone whose session is still active
 * after a super admin blocks them.
 */
class EnsureAccountIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isBlocked()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'This account has been blocked. Contact an administrator.');
        }

        return $next($request);
    }
}
