<?php

namespace App\Http\Controllers;

use App\Services\SiteStatus;
use Illuminate\Http\Response;

/**
 * ★ THE DEAD-MAN'S SWITCH — "is anything still watching?", and nothing else.
 *
 * Report watchman-and-push (2026-08-16) built it to answer for ONE thing: is
 * the five-minute site check still running. Report watch-the-watcher
 * (2026-08-19) widened it, because an external monitor pointed at the narrow
 * version would have watched the wrong thing. R-0217's finding 39: every alarm
 * on this box reaches Habib through a scheduler, and if a scheduler is emptied
 * — precisely what was done to the old server — the alarms go quiet and
 * nothing says so.
 *
 * So it now answers for the WHOLE chain: the site check, and the nightly
 * backup, the storage sync and the binlog ship. One URL, one monitor, and if
 * any link has stopped it stops returning 200.
 *
 * ── ★★ IT MUST LEAK NOTHING ───────────────────────────────────────────────
 * This is a public URL on the open internet with no authentication, because a
 * dead-man's switch that needs a credential is a dead-man's switch that fails
 * closed when the credential expires. So the body is two tokens and nothing
 * else: a word and a number of seconds. No site names, no counts, no
 * statuses, no filenames, no sizes, no hostname, no version, no framework
 * banner. Every extra field would be a gift to a stranger.
 *
 * ⚠ AND IT DOES NOT SAY WHICH LINK IS BROKEN. That is not an oversight and it
 * is not a limitation to be fixed later: "something has stopped" is all a
 * public URL may ever say, and naming the backup that failed would put his
 * farm's state on the open internet. WHICH one is wrong goes to his phone,
 * where it belongs, through aqua:backup-watch.
 *
 * ── ⚠ IT OWNS NO THRESHOLD OF ITS OWN, ON PURPOSE ────────────────────────
 * The backup rules — did the nightly run, did the storage sync succeed, is the
 * binlog ship recent enough, and how long a verdict is good for — all live in
 * aqua:backup-watch in the aquaculture application, which is where they were
 * argued and where they are tested. Repeating any of those numbers here would
 * create two pieces of code with an opinion about whether a backup is healthy,
 * and two pieces of code disagreeing about that is worse than one.
 *
 * What crosses the wall between the two applications is therefore not a fact
 * to be re-judged but a VERDICT WITH A SHELF LIFE: a file whose mtime is when
 * the verdict was reached and whose contents are how many seconds it is good
 * for. This endpoint adds those two numbers and compares them to the clock.
 * That is its entire arithmetic.
 *
 * ── ★ IT DOES NOT DEPEND ON THE THING IT WATCHES, AND THAT IS NOW MEASURED ─
 * This handler makes no database query, resolves no tenant and starts no
 * session — it is registered outside the web middleware group precisely so
 * that StartSession (SESSION_DRIVER=database here) cannot make a health probe
 * open MySQL. It stats two files. A missing or unreadable one is STALE, which
 * is the honest answer and not an exception.
 *
 * ⚠ AND THE CLAIM IS ABOUT THE WHOLE ROUTE, NOT ABOUT THESE LINES, BECAUSE
 * THAT IS EXACTLY HOW IT WAS WRONG TWICE. It read "no database query" flatly
 * in R-0149 and again here, argued from the handler both times, and the
 * handler was never the problem: CACHE_STORE is database on this deployment,
 * so `throttle:30,1` in FRONT of the handler took a MySQL connection on every
 * request. Measured 2026-08-19 (report watch-the-watcher): ten requests, ten
 * connections, three bursts, against an idle control of none.
 *
 * ★ FIXED THE SAME DAY (report limiter-off-mysql), and the sentence is only
 * written here because it was re-measured after the fix rather than reasoned
 * about. The route's throttle is now ThrottleWithoutDatabase, which counts on
 * the FILE cache store, and the same measurement gives:
 *
 *   before   10 requests -> +10 MySQL connections, three bursts, idle +0
 *   after    10 requests -> + 0 MySQL connections, three bursts, idle +0
 *
 * and the endpoint was requested with every one of this application's MySQL
 * connections pointed at a closed port: an ordinary console page returned 500
 * and this URL returned 200 OK. The same route put back on `throttle:30,1`
 * under the same conditions returned 500 — which is the control that makes
 * the 200 mean anything. The throttle itself is unchanged and was re-proved
 * by requesting: 29 more requests inside a minute, then 429.
 *
 * What it still cannot survive is named rather than glossed: PHP-FPM being
 * down, nginx being down, this box being unreachable, or the disk that holds
 * the limiter's counter being full or read-only. Those take it to no answer
 * at all — which a monitor reads as a failure, and that is the safe direction.
 */
class WatchmanController extends Controller
{
    /**
     * How old the last SITE CHECK may be before this reports STALE.
     *
     * This one number stays here because the check it measures lives in this
     * application: saas:site-check writes the heartbeat a few lines away in
     * SiteStatus, so the rule and the thing it rules are still in one place.
     * The backup rules are not, which is why they are not repeated here.
     *
     * The check runs every five minutes, so this is two whole missed runs plus
     * a minute of slack: a single slow or skipped run — a deploy, a restart, a
     * cron minute that lost a race — never pages anybody, and a scheduler that
     * has actually stopped is caught inside a quarter of an hour.
     */
    public const STALE_AFTER_SECONDS = 900;

    /**
     * ── WHY THE RULE IS INLINE AND NOT IN THE SHARED PACKAGE ──────────────
     * It was, for about ten minutes, and the endpoint returned HTTP 500. This
     * pool's open_basedir is its own deployment and nothing else, so a WEB
     * request here cannot autoload a class through the vendor symlink into
     * /home/khansdine/packages — the same wall that made this app take a copy
     * of the shared language switcher, and the same wall that makes the backup
     * verdict arrive as a file inside this deployment rather than through the
     * shared run-state directory the two applications otherwise talk through.
     * So the few lines that matter live here, and they are covered by a
     * black-box probe rather than a unit test, which the report says out loud.
     */
    public function __invoke(): Response
    {
        // Each signal answers with the unix time at which it stops being
        // believable. The check is the same subtraction for every one of them.
        $deadlines = [
            $this->siteCheckDeadline(),
            $this->backupDeadline(),
        ];

        // -1 is "one of them has never reported, or cannot be read at all",
        // and it is a FAILURE. A monitor must never read "we have never
        // looked" as "everything is fine".
        if (in_array(null, $deadlines, true)) {
            return $this->plain('STALE -1', 503);
        }

        // The worst link decides. Zero or positive means something has reached
        // or passed its deadline, and by how long; negative is the slack left
        // on the tightest one. One number, one meaning in both directions.
        //
        // ⚠ ZERO IS STALE, NOT OK, and that is not pedantry. A shelf life of
        // zero seconds is exactly how aqua:backup-watch says "I found a problem,
        // do not believe this" - and with a "> 0" test that verdict read as
        // HEALTHY for its first whole second. Caught by the probe on
        // 2026-08-19, not by reasoning about it. "Valid for n seconds" has
        // stopped being valid at n.
        $over = max(array_map(fn (int $d): int => time() - $d, $deadlines));

        return $over >= 0
            ? $this->plain('STALE ' . $over, 503)
            : $this->plain('OK ' . (-$over), 200);
    }

    /**
     * When the last site check stops being believable, or null if there has
     * never been one.
     *
     * Reads the HEARTBEAT, not the state file: the state file lives in the
     * shared saas-runs directory, which this pool's open_basedir does not
     * include, so a web request cannot stat it. The heartbeat is one file
     * inside the deployment whose mtime is the answer — no parsing, no
     * timezone, and nothing in it worth reading.
     */
    private function siteCheckDeadline(): ?int
    {
        $m = $this->mtime(SiteStatus::heartbeatPath());

        return $m === null ? null : $m + self::STALE_AFTER_SECONDS;
    }

    /**
     * ★ WHEN THE BACKUP VERDICT STOPS BEING BELIEVABLE.
     *
     * aqua:backup-watch, in the OTHER application and on the other schedule,
     * writes this file every time it runs: mtime is when it decided, contents
     * are how many seconds that decision is good for. A healthy run writes a
     * long shelf life; a run that found any problem writes zero, which is
     * already expired, so a broken backup flips this switch at the next
     * request rather than a day later.
     *
     * Three different failures therefore arrive as the same subtraction:
     *   a backup job failed        the verdict was stamped expired
     *   the watcher stopped        nobody restamped it and it aged out
     *   this box stopped           there is no answer at all, and the monitor
     *                              fires on the absence
     *
     * ⚠ A file that is missing, unreadable or does not hold a plain number is
     * null — STALE. It fails toward "say something is wrong", which is the
     * only safe direction for a dead-man's switch: the alternative is a switch
     * that reports healthy because it cannot see.
     */
    private function backupDeadline(): ?int
    {
        $path = storage_path('app/backup-heartbeat');
        $m = $this->mtime($path);
        if ($m === null) {
            return null;
        }

        $body = @file_get_contents($path);
        if ($body === false || ! preg_match('/^\d+$/', trim($body))) {
            return null;
        }

        return $m + (int) trim($body);
    }

    /** A file's mtime, or null if it is missing or cannot be read. */
    private function mtime(string $path): ?int
    {
        try {
            if (! is_file($path)) {
                return null;
            }
            $m = @filemtime($path);

            return $m === false ? null : $m;
        } catch (\Throwable $e) {
            // Unreadable is indistinguishable from never, and both are STALE.
            return null;
        }
    }

    private function plain(string $body, int $status): Response
    {
        return response($body, $status, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-store',
            // Nothing here is for a person, and nothing here is for an index.
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
