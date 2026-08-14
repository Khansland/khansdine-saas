<?php

use Illuminate\Support\Facades\Schedule;

// The cached tenant statistics are refreshed in the CONSOLE, because the web
// process cannot open a tenant database and must not be able to.
Schedule::command('saas:stats')->hourly()->withoutOverlapping();
