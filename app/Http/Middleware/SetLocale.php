<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * bn first, because the reader this page is written for reads Bengali.
 *
 * The order is: a choice made in THIS session, then the shared kd_locale
 * cookie, then Bengali. The cookie is domain-wide across .khansdine.com.bd, so
 * it is honoured but does not outrank a choice made here.
 */
class SetLocale
{
    public const SUPPORTED = ['bn', 'en', 'ru'];

    public function handle(Request $request, Closure $next)
    {
        $chosen = $request->hasSession() ? $request->session()->get('kd_locale_chosen') : null;
        $locale = in_array($chosen, self::SUPPORTED, true)
            ? $chosen
            : $request->cookie('kd_locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'bn';
        }

        App::setLocale($locale);
        \Carbon\Carbon::setLocale($locale);

        return $next($request);
    }
}
