<?php

use Illuminate\Support\Facades\Schedule;

// The cached tenant statistics are refreshed in the CONSOLE, because the web
// process cannot open a tenant database and must not be able to.
// ★ Lock 1 min, not a day (AG-0080, R-0584): worst 4 s (its tenant_stats rows, 10:00:04 UTC),
// x5 rounded up. On the database store, exactly as exposed to a MySQL restart as site-check was.
Schedule::command('saas:stats')->hourly()->withoutOverlapping(1);

// ★ IS ANYTHING SERVING? — report site-uptime-watch, 2026-08-16.
//
// Seven customer-facing storefronts were dead from 14 August 16:49 to 16
// August 09:05 and it surfaced only because an unrelated run happened to
// request them. Nothing on this box watched an HTTP status.
//
// EVERY FIVE MINUTES, and the interval is chosen for one reason: the useful
// part of this screen is not "down" but "down for how long", and a five-minute
// grain makes that number worth reading. The cost is fifteen sites times two
// probes times 288 runs — about a tenth of a request per second across the
// whole fleet, which is nothing for nginx and nothing for the edge.
//
// It runs HERE because this deployment's scheduler demonstrably executes
// (saas:stats' rows carry a timestamp from the last hour) and because this is
// the screen he opens. withoutOverlapping so a slow run never stacks.
//
// ★★ AND THE LOCK EXPIRES IN 15 MINUTES, NOT 24 HOURS — AG-0079, R-0583.
// withoutOverlapping() with no argument holds its lock for 1,440 minutes. On
// 2026-10-16 MySQL restarted at 03:15 mid-run, the lock row in cache_locks
// was never released, and every run was skipped until it was deleted by hand
// at 14:14 (R-0580, R-0581) — eleven hours of a frozen uptime screen. The
// run length, measured: 17-19 s (14:15:17 and 08:55:19 UTC, 2026-10-16). The
// worst it can be, by construction: 19 entries x 2 probes x the 10 s
// request timeout = 380 s, 6.3 min. 15 minutes is 2.4 times that worst case,
// so a real run is never overlapped, and a stranded lock costs at most two
// skipped runs. aqua:site-alerts says so on his phone after 30 minutes.
Schedule::command('saas:site-check')
    ->everyFiveMinutes()
    ->withoutOverlapping(15)
    ->onOneServer();
