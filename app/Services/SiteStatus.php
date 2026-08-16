<?php

namespace App\Services;

use Khansdine\SubdomainShared\Support\SiteVerdict;

/**
 * THE STATE FILE, AND READING IT BACK — the same mechanism the console already
 * uses for per-tenant job outcomes, not a second one.
 *
 * aqua:each-tenant writes saas-runs/tenant-runs.json and
 * Console\TenantController reads it: a JSON file in the shared directory both
 * deployments can reach, written by a scheduled command, read read-only by a
 * screen, and a reader that NEVER throws because a missing or malformed file
 * is itself an answer the page should show.
 *
 * This is the same thing for sites. A separate FILE because the key is a site
 * and not a tenant — but the same directory, the same discipline, the same
 * four-states-never-a-blank rule as BackupEvidence.
 *
 * ── WHY "DOWN SINCE" IS CARRIED FORWARD ───────────────────────────────────
 * A screen that says a site is down is half the answer; "and it has been for
 * two days" is the half that makes somebody act. Each write compares against
 * the previous file: a site that was already down keeps its first-seen
 * timestamp, and a site that has just recovered loses it.
 */
class SiteStatus
{
    /**
     * ★ THE HEARTBEAT, INSIDE THE DEPLOYMENT.
     *
     * The state file lives in the shared saas-runs directory, which a WEB
     * request on this console cannot open at all: its php-fpm pool sets
     * open_basedir to its own deployment, deliberately, and BackupEvidence
     * already records that lesson. The dead-man's-switch endpoint is a web
     * request, so it cannot read the state file — it reads this instead.
     *
     * One file, inside open_basedir, whose MTIME is the whole answer. It
     * carries the timestamp as text as well, for a person looking at the disk,
     * but the endpoint uses the mtime: no parsing, no timezone, no format to
     * get wrong.
     */
    public static function heartbeatPath(): string
    {
        return storage_path('app/site-check-heartbeat');
    }

    public static function path(): string
    {
        return rtrim((string) config('saas.run_state_dir', dirname(base_path()) . '/saas-runs'), '/')
            . '/site-checks.json';
    }

    /**
     * Write the outcome. Returns null on success, a message otherwise.
     *
     * @param  array{checked_at:string, could_not_check:bool, sites:array}  $result
     */
    public static function record(array $result): ?string
    {
        try {
            $previous = self::read();
            $was = [];
            foreach (($previous['sites'] ?? []) as $p) {
                $was[$p['key']] = $p;
            }

            foreach ($result['sites'] as &$s) {
                if ($s['state'] === SiteVerdict::DOWN) {
                    // Already down? keep the moment it started. Newly down? now.
                    $s['down_since'] = $was[$s['key']]['down_since'] ?? $result['checked_at'];
                } else {
                    $s['down_since'] = null;
                }

                // ★ THE TWO-IN-A-ROW COUNTER, kept where the history is.
                //
                // The alert condition Habib accepted is DOWN on BOTH the edge
                // and the origin for TWO CONSECUTIVE checks. Only this writer
                // sees the previous run, so only this writer can count. The
                // sender reads the number and decides nothing about history.
                //
                // Counting the ALERT condition and not merely "edge is down"
                // matters: a site failing at the edge for an hour and then
                // failing at the origin once would otherwise page immediately,
                // on a single observation of the thing we alert about.
                $bothDown = $s['state'] === SiteVerdict::DOWN
                    && (! isset($s['origin']) || $s['origin']['state'] === SiteVerdict::DOWN);
                $s['alert_streak'] = $bothDown
                    ? (int) ($was[$s['key']]['alert_streak'] ?? 0) + 1
                    : 0;
            }
            unset($s);

            $dir = dirname(self::path());
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            file_put_contents(self::path(),
                json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            @chmod(self::path(), 0664);

            // The heartbeat the public endpoint reads. Written last, so it is
            // only touched by a run that actually completed and recorded.
            try {
                @file_put_contents(self::heartbeatPath(), $result['checked_at'] . "\n");
                @chmod(self::heartbeatPath(), 0644);
            } catch (\Throwable $e) {
                // A heartbeat that cannot be written makes the switch report
                // STALE, which is the correct failure direction.
            }

            return null;
        } catch (\Throwable $e) {
            return 'Could not write the site-check record: ' . $e->getMessage();
        }
    }

    /**
     * What the last run found, or null if there has never been one.
     *
     * NEVER THROWS. A missing file means NEVER CHECKED, which is a state the
     * screen must show in its own words — not silently as "fine".
     */
    public static function read(): ?array
    {
        try {
            $p = self::path();
            if (! is_readable($p)) {
                return null;
            }
            $j = json_decode((string) file_get_contents($p), true);

            return is_array($j) ? $j : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
