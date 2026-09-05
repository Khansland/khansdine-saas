<?php

namespace App\Console\Commands;

use App\Services\SiteHistory;
use Illuminate\Console\Command;

/**
 * ★ ASK THE WATCHMAN WHAT IT SAW. R-0431.
 *
 * The question R-0430 could not answer: "what was agriculture doing at 18:23?"
 * This is the command that answers it, from the history the check now keeps.
 *
 *   php artisan saas:site-history --at="2026-09-05 18:23"
 *   php artisan saas:site-history --at="2026-09-05 18:23" --site=agriculture
 *   php artisan saas:site-history --at=... --window=15
 */
class SiteHistoryCommand extends Command
{
    protected $signature = 'saas:site-history
        {--at= : the moment to ask about, any format PHP understands. Default: now}
        {--site= : one site key. Default: all of them}
        {--window=5 : minutes either side to include}
        {--tz= : the clock --at is read on. Default: the farm\'s}';

    protected $description = 'What the site check saw at a named minute';

    /** Render a stored UTC instant on the clock the question was asked in. */
    private static function onClock(?string $iso, string $tz): string
    {
        if (! $iso) {
            return '-';
        }
        try {
            return (new \DateTimeImmutable($iso))->setTimezone(new \DateTimeZone($tz))->format('Y-m-d H:i:s T');
        } catch (\Throwable $e) {
            return $iso;
        }
    }

    public function handle(): int
    {
        $at = (string) ($this->option('at') ?: 'now');
        $site = $this->option('site') ?: null;
        $window = (int) $this->option('window');

        try {
            $tz = (string) ($this->option('tz') ?: SiteHistory::CLOCK);
            $rows = SiteHistory::at($at, $site ? (string) $site : null, $window, $tz);
        } catch (\Throwable $e) {
            $this->error('Could not read that moment: ' . $e->getMessage());

            return self::FAILURE;
        }

        if ($rows === []) {
            // ★ NOT "it was fine". Nothing was recorded, and that is its own
            // answer - the same discipline as cannot_check.
            $this->warn(sprintf('NOTHING RECORDED within %d min of %s%s. '
                . 'That is ignorance, not health — history is kept for %d days.',
                $window, $at, $site ? " for {$site}" : '', SiteHistory::RETENTION_DAYS));

            return self::FAILURE;
        }

        $this->table(
            ['when', 'site', 'probe', 'state', 'HTTP', 'bytes', 'ms', 'why'],
            array_map(fn ($r) => [
                self::onClock($r['at'] ?? null, $tz), $r['site'] ?? '-', $r['probe'] ?? '-',
                strtoupper(str_replace('_', ' ', (string) ($r['state'] ?? '-'))),
                $r['status'] ?? '-', $r['bytes'] ?? '-', $r['ms'] ?? '-',
                $r['why'] ?? '',
            ], $rows)
        );
        $this->info(sprintf('%d probe records within %d min of %s, on the %s clock.',
            count($rows), $window, $at, $tz));

        return self::SUCCESS;
    }
}
