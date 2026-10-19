<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

/**
 * ★★ THE UPTIME CHECK RUNS OUTSIDE THIS APP, AND WATCHES THIS LIST — R-0597 (AG-0088 B2).
 *
 * saas:site-check ran from this app's scheduler; it is now a plain script in the
 * khansdine crontab (/home/khansdine/uptime/uptime-check.sh) that cannot read PHP
 * config, so it reads its own copy of the list: /home/khansdine/uptime/sites.json.
 * config/sites.php stays "the one list of what should be up". A site added here and
 * not there would never be watched - so the two must be equal, entry for entry.
 */
class TheUptimeCheckWatchesThisListTest extends TestCase
{
    private const SCRIPT_LIST = '/home/khansdine/uptime/sites.json';

    public function test_the_scripts_list_is_this_list(): void
    {
        $this->assertFileExists(self::SCRIPT_LIST, 'the uptime script has no list to read');
        $script = json_decode((string) file_get_contents(self::SCRIPT_LIST), true);

        $this->assertSame(config('sites.entries'), $script['entries'] ?? null,
            'config/sites.php and ' . self::SCRIPT_LIST . ' differ: export the list again (R-0597)');
        $this->assertSame(config('sites.origin_ip'), $script['origin_ip'] ?? null);
    }

    public function test_this_app_no_longer_schedules_the_check(): void
    {
        $commands = array_map(fn ($e) => (string) $e->command, $this->app->make(Schedule::class)->events());

        $this->assertNotEmpty($commands, 'no schedule loaded: the assertion below would prove nothing');
        $this->assertSame([], array_values(array_filter($commands, fn ($c) => str_contains($c, 'saas:site-check'))),
            'saas:site-check is scheduled again inside the app it checks');
    }
}
