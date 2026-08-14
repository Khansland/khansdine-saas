<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function show()
    {
        if (Auth::guard('console')->check()) {
            return redirect()->route('console.tenants');
        }

        return view('console.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // A console that can delete a customer's database gets a throttle, and
        // the throttle is keyed on the address, not the address AND the email:
        // otherwise trying a thousand emails from one machine costs nothing.
        $key = 'console-login:' . AuditEvent::hashIp($request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors([
                'email' => __('saas.auth.throttled', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        if (! Auth::guard('console')->attempt($data, (bool) $request->boolean('remember'))) {
            RateLimiter::hit($key, 900);
            AuditEvent::record('console.login_failed', null, null, ['email' => $data['email']]);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __('saas.auth.failed')]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        Auth::guard('console')->user()->forceFill(['last_login_at' => now()])->save();
        AuditEvent::record('console.login');

        return redirect()->intended(route('console.tenants'));
    }

    public function logout(Request $request)
    {
        AuditEvent::record('console.logout');
        Auth::guard('console')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
