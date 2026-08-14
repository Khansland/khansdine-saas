<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // kd_locale is the shared package's language cookie and is written for
        // the whole .khansdine.com.bd domain, so it must stay readable in the
        // clear. The SESSION cookie is a different matter entirely: it is named
        // and scoped for this host alone in .env, so a console session is never
        // sent to a tenant and a tenant's session is never sent here.
        // This host will sit BEHIND the Cloudflare proxy, unlike the demo. The
        // origin then sees plain http and Cloudflare's address, so without this
        // the rate limiter would key every visitor to the same address and
        // every generated link would be http. Trusting the proxy headers is
        // safe here because nothing but Cloudflare can reach the origin on 443
        // once the record is proxied - and if that ever changes, so must this.
        $middleware->trustProxies(at: '*', headers:
            Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
            | Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
            | Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
            | Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO);

        $middleware->encryptCookies(except: ['kd_locale']);

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'console.auth' => \App\Http\Middleware\RequireConsoleUser::class,
        ]);

        // A guest who reaches a console URL is sent to the console login, never
        // to an SSO host: this app is not part of that family.
        $middleware->redirectGuestsTo(fn () => route('console.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
