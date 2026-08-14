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
    protected $fillable = [
        'subdomain', 'database_name', 'tanks', 'batches', 'users',
        'db_bytes', 'last_backup_at', 'collected_at', 'error',
    ];

    protected function casts(): array
    {
        return ['collected_at' => 'datetime', 'last_backup_at' => 'datetime'];
    }
}
