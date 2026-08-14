<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * The tenant list, read through a SELECT-only MySQL grant.
 *
 * Every method here reads. There is deliberately no write path: a tenant's
 * status changes because a console command changed it, never because a web
 * request did. If a future screen needs to write here, that is the moment to
 * stop and reconsider, not the moment to add a method.
 */
class Registry
{
    public const CONNECTION = 'registry';

    public static function tenants()
    {
        return DB::connection(self::CONNECTION)->table('tenants')
            ->orderBy('subdomain')
            ->get();
    }

    public static function find(string $subdomain)
    {
        return DB::connection(self::CONNECTION)->table('tenants')
            ->where('subdomain', $subdomain)
            ->first();
    }

    /** Is the registry reachable at all? The list page says so rather than 500ing. */
    public static function healthy(): bool
    {
        try {
            DB::connection(self::CONNECTION)->select('SELECT 1');

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
