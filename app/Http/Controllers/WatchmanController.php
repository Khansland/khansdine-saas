<?php

namespace App\Http\Controllers;

use App\Services\SiteStatus;
use Illuminate\Http\Response;

/**
 * ★ THE DEAD-MAN'S SWITCH — "is the watchman alive?", and nothing else.
 *
 * Report watchman-and-push (2026-08-16). saas:site-check now watches fifteen
 * sites every five minutes, and site-uptime-watch flagged its own hole in the
 * same breath: nothing watches the watchman. If the scheduler stops, the
 * console keeps showing the last good result with an increasingly old
 * timestamp, and only somebody reading that timestamp would notice.
 *
 * This endpoint exists so a monitor OUTSIDE this box can notice instead. It
 * answers 200 while the last check is recent and a non-200 the moment it is
 * not, which is the whole contract.
 *
 * ── ★★ IT MUST LEAK NOTHING ───────────────────────────────────────────────
 * This is a public URL on the open internet with no authentication, because a
 * dead-man's switch that needs a credential is a dead-man's switch that fails
 * closed when the credential expires. So the body is two tokens and nothing
 * else: a word and an age in seconds. No site names, no counts, no statuses,
 * no hostname, no version, no framework banner. Every extra field would be a
 * gift to a stranger.
 *
 * ── AND IT DELIBERATELY DOES NOT SAY WHETHER SITES ARE DOWN ───────────────
 * "Is the watchman alive" and "is anything broken" are different questions,
 * and answering the second one here would put the fleet's health on a public
 * URL. A stranger polling this learns exactly one thing: that a scheduled task
 * on some machine ran recently. That is the most it may ever learn.
 *
 * ── IT MUST NOT DEPEND ON THE THING IT WATCHES ────────────────────────────
 * No database query, no tenant resolution, no session — it is registered
 * outside the web middleware group precisely so that starting a session
 * (SESSION_DRIVER=database here) cannot make a health probe depend on MySQL.
 * It reads one file's timestamp. A missing or unreadable file is STALE, which
 * is the honest answer and not an exception.
 */
class WatchmanController extends Controller
{
    /**
     * How old the last check may be before this reports STALE.
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
     * of the shared language switcher. The console's CLI can (saas:site-check
     * uses SiteVerdict happily); its web process cannot. So the four lines
     * that matter live here, and they are covered by a black-box probe rather
     * than a unit test, which the report says out loud.
     */
    public function __invoke(): Response
    {
        $age = $this->ageSeconds();

        // -1 is "never checked", and it is a FAILURE. A monitor must never
        // read "we have never looked" as "everything is fine".
        if ($age === null) {
            return $this->plain('STALE -1', 503);
        }

        return $age <= self::STALE_AFTER_SECONDS
            ? $this->plain('OK ' . $age, 200)
            : $this->plain('STALE ' . $age, 503);
    }

    /**
     * Seconds since the last recorded check, or null if there has never been one.
     *
     * Reads the HEARTBEAT, not the state file: the state file lives in the
     * shared saas-runs directory, which this pool's open_basedir does not
     * include, so a web request cannot stat it. The heartbeat is one file
     * inside the deployment whose mtime is the answer — no parsing, no
     * timezone, and nothing in it worth reading.
     */
    private function ageSeconds(): ?int
    {
        try {
            $p = SiteStatus::heartbeatPath();
            if (! is_file($p)) {
                return null;
            }
            $m = @filemtime($p);

            return $m === false ? null : max(0, time() - $m);
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
