<?php

namespace App\Http\Middleware;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * ★ THE THROTTLE FOR THE DEAD-MAN'S SWITCH, AND FOR NOTHING ELSE.
 *
 * The switch exists to answer when other things are broken. Until 2026-08-19
 * it could not: CACHE_STORE is database on this deployment, so the ordinary
 * `throttle:30,1` middleware resolved onto the database cache store and took a
 * MySQL connection on EVERY request to a URL whose whole purpose is to be
 * reachable when MySQL is not. Measured, not argued, in report
 * watch-the-watcher: ten requests, ten connections, none at all with the
 * throttle lifted. The handler had always been clean; the route was not.
 *
 * This class is that one limiter moved onto the file store, and it is a class
 * rather than a setting for a reason: THE CHANGE MUST REACH ONE ROUTE.
 *
 * ── WHY NOT THE THREE EASIER WAYS ─────────────────────────────────────────
 *   CACHE_STORE=file           moves every cache on the console - the tenant
 *                              registry, the config cache, everything - to
 *                              answer a question about one URL.
 *   cache.limiter => 'file'    looks narrow and is not. Laravel builds ONE
 *                              RateLimiter singleton from that key
 *                              (CacheServiceProvider), and the console login
 *                              throttle in AuthController uses the same
 *                              singleton through the RateLimiter facade. That
 *                              throttle guards a login that can delete a
 *                              customer's database; it does not get moved as
 *                              a side effect of a health check.
 *   RateLimiter::for('...')    does not do it at all. A named limiter names a
 *                              LIMIT - how many, how long, keyed on what - and
 *                              is then evaluated by that same singleton, on
 *                              that same store. It cannot change where the
 *                              counter is written.
 *
 * The store belongs to the RateLimiter instance, and the RateLimiter instance
 * is a constructor argument of the middleware. So the narrowest place to put
 * the decision is a middleware with its own instance, which is this.
 *
 * ── ⚠ IT WRITES TO DISK, SO SAY WHERE AND WHO ────────────────────────────
 * cache.stores.file.path is storage/framework/cache/data inside THIS
 * deployment - which matters twice over: this pool's open_basedir is the
 * deployment and nothing else, so a path outside it would fail on the web and
 * work on the CLI; and php-fpm runs this pool as khansdine, who owns that
 * directory. A limiter that cannot write is a limiter that throws, on the one
 * URL that must never throw.
 */
class ThrottleWithoutDatabase extends ThrottleRequests
{
    public function __construct(CacheFactory $cache)
    {
        parent::__construct(new RateLimiter($cache->store('file')));
    }
}
