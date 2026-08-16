<?php

namespace App\Console\Commands;

use App\Services\SiteCheck;
use App\Services\SiteStatus;
use Illuminate\Console\Command;
use Khansdine\SubdomainShared\Support\SiteVerdict;

/**
 * saas:site-check — ask every site in config/sites.php whether it is there,
 * and write the answer where the console reads it.
 *
 * ── WHY THIS DEPLOYMENT RUNS IT ───────────────────────────────────────────
 * Three deployments on this box run `schedule:run` from cron. The console's is
 * the one to trust with this, for two reasons a guess would have got wrong:
 *  - its scheduler DEMONSTRABLY executes. saas:stats is scheduled hourly here
 *    and its rows carry a timestamp from the last hour. The tenant
 *    deployment's eight jobs silently failed for its entire life because cron
 *    has no HTTP host and its tenancy is resolved from one — that is exactly
 *    the class of mistake this command exists to catch, and it must not be
 *    the class of mistake this command makes.
 *  - the console is the fleet's screen. It already knows about every tenant,
 *    already carries BackupEvidence in the same shape, and is the page Habib
 *    opens when he wants to know whether things are all right.
 *
 * The honest limit of that choice is stated on the screen: if the console
 * itself is down, nothing checks and nothing is displayed. It is in the list
 * so that a run which DOES happen still reports on it.
 */
class SiteCheckCommand extends Command
{
    protected $signature = 'saas:site-check {--only=* : restrict to these keys} {--print : also print the table}';

    protected $description = 'Request every site in the list and record whether it answered as it should';

    public function handle(SiteCheck $check): int
    {
        $only = $this->option('only') ?: null;
        $result = $check->run($only);

        $warning = SiteStatus::record($result);
        if ($warning !== null) {
            // Recording must never be the thing that breaks the run.
            $this->warn($warning);
        }

        $down = 0;
        $rows = [];
        foreach ($result['sites'] as $s) {
            if ($s['state'] === SiteVerdict::DOWN) {
                $down++;
            }
            $rows[] = [
                $s['key'],
                strtoupper(str_replace('_', ' ', $s['state'])),
                $s['status'] ?? '-',
                $s['bytes'] ?? '-',
                ($s['ms'] ?? '-') . ' ms',
                isset($s['origin']) ? strtoupper(str_replace('_', ' ', $s['origin']['state'])) : '-',
                $s['why'] ?? ($s['origin']['why'] ?? ''),
            ];
        }

        if ($this->option('print')) {
            $this->table(['site', 'edge', 'HTTP', 'bytes', 'time', 'origin', 'why'], $rows);
        }

        if ($result['could_not_check']) {
            // ★ NOT "everything is down".
            $this->warn('COULD NOT CHECK: not one site answered. Reporting ignorance, not an outage.');

            return self::FAILURE;
        }

        $this->info(sprintf('%d checked, %d down.', count($result['sites']), $down));

        return $down > 0 ? self::FAILURE : self::SUCCESS;
    }
}
