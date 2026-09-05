<?php

namespace App\Services;

/**
 * ★ WHAT WAS IT DOING AT 18:23? — the question nothing on this box could answer.
 *
 * R-0430 spent a run bounding an outage window by counting session files,
 * because the site check kept NO history at all: site-checks.json holds only
 * the last run, down_since is cleared the moment a site recovers, and
 * site-alerts.log is six thousand identical untimestamped lines. Even a
 * perfectly correct instrument could not say, after the fact, what agriculture
 * was doing at a named minute. R-0431 makes it able to.
 *
 * ── WHY A ROTATING FILE AND NOT A TABLE ───────────────────────────────────
 *  - It lives in the shared saas-runs directory, OUTSIDE any deployment, which
 *    is the same seam tenant-runs.json and site-checks.json already use. A
 *    deploy, a fresh checkout or a rebuilt vendor/ cannot touch it.
 *  - It is readable when the database is not. The moment you most want to know
 *    what the fleet was doing is an incident, and an incident is exactly when
 *    "read it out of MySQL" may not be available.
 *  - Retention is a file deletion, not a DELETE against a table holding rows.
 *
 * ── IT MUST NOT GROW WITHOUT BOUND ────────────────────────────────────────
 * 15 sites x 2 probes every 5 minutes is 8,640 lines a day, about 1.5 MB. One
 * file per day, and files older than RETENTION_DAYS are removed on each write.
 * At 14 days that is a ceiling of roughly 21 MB, reached in a fortnight and
 * never exceeded.
 */
class SiteHistory
{
    /** Days of history kept. Older daily files are deleted on every append. */
    public const RETENTION_DAYS = 14;

    /**
     * The clock a person on this box reads. Records are stored in the app's
     * timezone (UTC); this is the one a question is asked in and an answer is
     * printed in. See at().
     */
    public const CLOCK = 'Asia/Dhaka';

    public static function dir(): string
    {
        return rtrim((string) config('saas.run_state_dir', dirname(base_path()) . '/saas-runs'), '/')
            . '/site-history';
    }

    public static function pathFor(string $date): string
    {
        return self::dir() . '/' . $date . '.jsonl';
    }

    /**
     * Append one line per PROBE for this tick. Never throws: recording must
     * never be the thing that breaks the run.
     *
     * @param  array{checked_at:string, sites:array}  $result
     */
    public static function append(array $result): ?string
    {
        try {
            $dir = self::dir();
            if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
                return 'could not create ' . $dir;
            }

            $at = $result['checked_at'] ?? now()->toIso8601String();
            $day = substr((string) $at, 0, 10);

            $lines = '';
            foreach (($result['sites'] ?? []) as $s) {
                $lines .= self::line($at, (string) $s['key'], 'edge', $s) . "\n";
                if (isset($s['origin']) && is_array($s['origin'])) {
                    $lines .= self::line($at, (string) $s['key'], 'origin', $s['origin']) . "\n";
                }
            }
            if ($lines === '') {
                return null;
            }

            $path = self::pathFor($day);
            $ok = @file_put_contents($path, $lines, FILE_APPEND | LOCK_EX);
            if ($ok === false) {
                return 'could not write ' . $path;
            }
            @chmod($path, 0664);

            self::prune();

            return null;
        } catch (\Throwable $e) {
            return 'site history not recorded: ' . $e->getMessage();
        }
    }

    /** @param array<string, mixed> $probe */
    private static function line(string $at, string $key, string $which, array $probe): string
    {
        return (string) json_encode([
            'at' => $at,
            'site' => $key,
            'probe' => $which,
            'state' => $probe['state'] ?? null,
            'status' => $probe['status'] ?? null,
            'location' => $probe['location'] ?? null,
            'bytes' => $probe['bytes'] ?? null,
            'ms' => $probe['ms'] ?? null,
            'why' => $probe['why'] ?? null,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** Delete daily files older than the retention window. */
    public static function prune(): int
    {
        $cut = (new \DateTimeImmutable('today'))->modify('-' . self::RETENTION_DAYS . ' days');
        $gone = 0;
        foreach (glob(self::dir() . '/*.jsonl') ?: [] as $f) {
            $day = basename($f, '.jsonl');
            try {
                if (new \DateTimeImmutable($day) < $cut) {
                    @unlink($f);
                    $gone++;
                }
            } catch (\Throwable $e) {
                // A filename that is not a date is not ours. Leave it alone.
            }
        }

        return $gone;
    }

    /**
     * ★ THE READ-BACK. What was this site doing at that minute?
     *
     * Returns every recorded probe within $windowMinutes either side of the
     * moment asked about, newest first, so "what was agriculture doing at
     * 18:23" has an answer even though the check only samples every five.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function at(string $when, ?string $site = null, int $windowMinutes = 5,
        string $tz = self::CLOCK): array
    {
        // ★ THE MOMENT IS READ ON THE CLOCK THE PERSON ASKING USES.
        //
        // checked_at is stored in the app's timezone, which is UTC, and that is
        // right - one unambiguous instant per record. But Habib asks about
        // 18:23 meaning 18:23 on the farm, and the first version of this
        // command silently answered "NOTHING RECORDED" because it read his
        // 18:23 as UTC and looked six hours away. A history that cannot be
        // asked in the asker's own clock is a history nobody will ask.
        $t = new \DateTimeImmutable($when, new \DateTimeZone($tz));
        $from = $t->modify('-' . $windowMinutes . ' minutes');
        $to = $t->modify('+' . $windowMinutes . ' minutes');

        $out = [];
        $seen = [];
        $utc = new \DateTimeZone('UTC');
        foreach ([$from, $t, $to] as $d) {
            // Files are named by the UTC date the records carry.
            $path = self::pathFor($d->setTimezone($utc)->format('Y-m-d'));
            if (! is_readable($path) || isset($seen[$path])) {
                continue;
            }
            $seen[$path] = true;
            $fh = @fopen($path, 'r');
            if (! $fh) {
                continue;
            }
            while (($l = fgets($fh)) !== false) {
                $r = json_decode(trim($l), true);
                if (! is_array($r) || ! isset($r['at'])) {
                    continue;
                }
                if ($site !== null && ($r['site'] ?? null) !== $site) {
                    continue;
                }
                try {
                    $rt = new \DateTimeImmutable($r['at']);
                } catch (\Throwable $e) {
                    continue;
                }
                if ($rt >= $from && $rt <= $to) {
                    $out[] = $r;
                }
            }
            fclose($fh);
        }

        usort($out, fn ($a, $b) => strcmp((string) $b['at'], (string) $a['at']));

        return $out;
    }
}
