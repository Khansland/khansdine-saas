<?php

namespace App\Console\Commands;

use App\Models\TenantStat;
use App\Services\Registry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Refresh what each tenant's install holds.
 *
 * WHY THIS IS A COMMAND AND NOT A QUERY ON THE DETAIL PAGE: the console's MySQL
 * user is granted saas_console and nothing else. It cannot open a tenant
 * database, and E.1 proves that on purpose. So the numbers are collected here,
 * where a human at a terminal supplies credentials that reach further, and the
 * screen shows them with the time they were taken.
 *
 * It reads with the credentials in the file the tenant deployment already uses
 * for provisioning, and it reads ONLY counts and sizes - never a row of a
 * customer's data.
 */
class StatsCommand extends Command
{
    protected $signature = 'saas:stats {--credentials=/home/khansdine/saas-admin.json}';

    protected $description = 'Refresh the cached per-tenant counts the console shows';

    public function handle(): int
    {
        if (! Registry::healthy()) {
            $this->error('The registry is not reachable.');

            return self::FAILURE;
        }

        $file = (string) $this->option('credentials');
        if (! is_readable($file)) {
            $this->error("No credentials at {$file}. This command is the only thing that reads them.");

            return self::FAILURE;
        }
        $cred = json_decode((string) file_get_contents($file), true);
        if (! is_array($cred) || ($cred['username'] ?? '') === '') {
            $this->error("{$file} has no username in it.");

            return self::FAILURE;
        }

        config([
            'database.connections.tenant_probe' => [
                'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => '3306',
                'database' => '', 'username' => $cred['username'],
                'password' => $cred['password'] ?? '', 'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => true,
            ],
        ]);

        $done = 0;
        foreach (Registry::tenants() as $t) {
            $row = ['subdomain' => $t->subdomain, 'database_name' => $t->database_name,
                    'collected_at' => now(), 'error' => null,
                    'tanks' => null, 'batches' => null, 'users' => null, 'db_bytes' => null];
            try {
                $db = $t->database_name;
                $row['db_bytes'] = (int) DB::connection('tenant_probe')->scalar(
                    'SELECT COALESCE(SUM(data_length + index_length), 0) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?',
                    [$db]
                );
                foreach (['tanks' => 'fish_tanks', 'batches' => 'fish_batches', 'users' => 'users'] as $key => $table) {
                    $exists = (int) DB::connection('tenant_probe')->scalar(
                        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                        [$db, $table]
                    );
                    $row[$key] = $exists
                        ? (int) DB::connection('tenant_probe')->scalar("SELECT COUNT(*) FROM `{$db}`.`{$table}`")
                        : null;
                }
                $row['last_backup_at'] = $this->lastBackup($db);
            } catch (\Throwable $e) {
                // A tenant that has not been created yet is not an error worth
                // failing the whole run for - it is a fact worth showing.
                $row['error'] = substr($e->getMessage(), 0, 180);
            }

            TenantStat::updateOrCreate(['subdomain' => $t->subdomain], $row);
            $done++;
            $this->line(sprintf('  %-16s %s', $t->subdomain, $row['error'] ? 'ERROR' : 'ok'));
        }

        $this->info("refreshed: {$done}");

        return self::SUCCESS;
    }

    /** The newest nightly dump for this database, if the backup can be seen from here. */
    private function lastBackup(string $db): ?string
    {
        foreach (['/home/khansland/backups', '/home/khansland/snapshots'] as $dir) {
            if (! is_dir($dir)) {
                continue;
            }
            $files = @glob($dir . '/' . $db . '_*.sql.gz') ?: [];
            if ($files) {
                return date('Y-m-d H:i:s', max(array_map('filemtime', $files)));
            }
        }

        return null;
    }
}
