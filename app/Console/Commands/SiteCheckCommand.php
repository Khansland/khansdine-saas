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

        // ★ THE COUNT SEES BOTH PROBES. R-0430.
        //
        // This counted only $s['state'] — the EDGE. The origin verdict was
        // measured, recorded and drawn in the table below while being absent
        // from the tally and from the exit code, so an entry whose edge
        // answered 200 and whose origin answered 403 printed "1 checked,
        // 0 down." and exited 0. Measured, not argued. Every closing
        // "15 checked, 0 down." from R-0417 to R-0429 was an edge-only claim.
        $down = 0;
        $rows = [];
        foreach ($result['sites'] as $s) {
            if (SiteVerdict::siteState($s)['down']) {
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

        // ★ NAME WHAT IT SAW. R-0430.
        //
        // "15 checked, 0 down." carried thirteen reports and said nothing a
        // reader could check: not which sites, not what they answered, not
        // whether the origin was asked at all. One line per site, with the
        // status from each probe, is the output that could not have hidden
        // this. It is printed ALWAYS, not only under --print, because the
        // scheduled run is the one nobody is watching.
        foreach (SiteVerdict::lines($result['sites']) as $line) {
            $this->line('  ' . $line);
        }

        if ($result['could_not_check']) {
            // ★ NOT "everything is down".
            $this->warn('COULD NOT CHECK: not one site answered. Reporting ignorance, not an outage.');

            return self::FAILURE;
        }

        $downKeys = [];
        foreach ($result['sites'] as $s) {
            if (SiteVerdict::siteState($s)['down']) {
                $downKeys[] = $s['key'];
            }
        }

        $this->info(sprintf('%d checked, %d down%s (edge AND origin both counted).',
            count($result['sites']), $down,
            $downKeys === [] ? '' : ': ' . implode(', ', $downKeys)));

        return $down > 0 ? self::FAILURE : self::SUCCESS;
    }
}
