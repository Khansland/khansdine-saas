<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * What a customer's install holds, as of the last time a CONSOLE command looked.
 *
 * The console's MySQL user cannot open a tenant database - that is the whole
 * point of E.1 - so these numbers cannot be read live from a web request. They
 * are refreshed by `saas:stats`, which runs in the console with credentials the
 * web process does not have, and every screen that shows them says how old they
 * are rather than implying they are live.
 */
class TenantStat extends Model
{
    /** Older than this and a backup is not a backup. */
    public const STALE_HOURS = 48;

    protected $fillable = [
        'kind', 'subdomain', 'database_name', 'tanks', 'batches', 'users',
        'db_bytes', 'last_backup_at', 'collected_at', 'error',
        'backup_state', 'backup_file', 'backup_bytes', 'backup_count', 'backup_note',
    ];

    protected function casts(): array
    {
        return ['collected_at' => 'datetime', 'last_backup_at' => 'datetime'];
    }

    /**
     * What the screen should say about this row's backup, as ONE of four words.
     *
     *   ok          a dump exists and it is recent enough
     *   stale       a dump exists and it is older than STALE_HOURS. Age matters
     *               more than existence: a backup from last week is not a backup.
     *   none_found  the directory was readable and holds no dump for it
     *   cannot_look nobody could look, and saying "none" would be a lie
     *
     * A row that has never been probed does not reach here at all - the view
     * says "never collected", which is a fifth thing and not this method's job.
     */
    public function getBackupVerdictAttribute(): string
    {
        if ($this->backup_state !== 'ok' || ! $this->last_backup_at) {
            return $this->backup_state ?: 'cannot_look';
        }

        return $this->last_backup_at->diffInHours(now()) > self::STALE_HOURS ? 'stale' : 'ok';
    }

    /** The newest dump's time, in the timezone the reader is standing in. */
    public function getBackupAtLocalAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->last_backup_at?->copy()->timezone(config('saas.display_timezone', 'UTC'));
    }

    public function getCollectedAtLocalAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->collected_at?->copy()->timezone(config('saas.display_timezone', 'UTC'));
    }

    public function getBackupAgeHoursAttribute(): ?int
    {
        return $this->last_backup_at ? (int) $this->last_backup_at->diffInHours(now()) : null;
    }

    /** A size a person can read, or null when there is nothing to size. */
    public function getBackupSizeAttribute(): ?string
    {
        if (! $this->backup_bytes) {
            return null;
        }
        $mb = $this->backup_bytes / 1048576;

        return $mb >= 1 ? number_format($mb, 1) . ' MB' : number_format($this->backup_bytes / 1024, 0) . ' KB';
    }
}
