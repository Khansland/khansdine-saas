<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The console guard. One guard, named, with no fallback.
 *
 * Auth::guard('console') is asked explicitly rather than through the default,
 * so a future config change that adds a second guard cannot quietly make this
 * page reachable by something else's session.
 */
class RequireConsoleUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('console')->user();

        if (! $user) {
            return redirect()->route('console.login');
        }

        // A deactivated account loses the console on its next request, not at
        // its next login: this is the screen that can delete a customer.
        if (! $user->is_active) {
            Auth::guard('console')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('console.login')
                ->withErrors(['email' => __('saas.auth.deactivated')]);
        }

        return $next($request);
    }
}
