<?php

use Illuminate\Support\Facades\Schedule;

// The cached tenant statistics are refreshed in the CONSOLE, because the web
// process cannot open a tenant database and must not be able to.
Schedule::command('saas:stats')->hourly()->withoutOverlapping();

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
Schedule::command('saas:site-check')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();
