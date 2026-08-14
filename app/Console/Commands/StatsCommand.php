<?php

namespace App\Console\Commands;

use App\Models\TenantStat;
use App\Services\BackupEvidence;
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
            $row = ['kind' => 'tenant', 'subdomain' => $t->subdomain,
                    'database_name' => $t->database_name,
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
                $row = array_merge($row, self::backupColumns($db));
            } catch (\Throwable $e) {
                // A tenant that has not been created yet is not an error worth
                // failing the whole run for - it is a fact worth showing. Its
                // backup evidence is still collected: "the database does not
                // exist" and "the database is not backed up" are two different
                // sentences and the screen says both.
                $row['error'] = substr($e->getMessage(), 0, 180);
                $row = array_merge($row, self::backupColumns($t->database_name));
            }

            TenantStat::updateOrCreate(['subdomain' => $t->subdomain], $row);
            $done++;
            $this->line(sprintf('  %-16s %s', $t->subdomain, $row['error'] ? 'ERROR' : 'ok'));
        }

        $this->systemDatabases();

        $this->info("refreshed: {$done} tenant(s) + 2 system databases");

        return self::SUCCESS;
    }

    /** The backup columns for one database, from the evidence on disk. */
    private static function backupColumns(string $database): array
    {
        $e = BackupEvidence::for($database);

        return [
            'last_backup_at' => $e['at'],
            'backup_state' => $e['state'],
            'backup_file' => $e['file'],
            'backup_bytes' => $e['bytes'],
            'backup_count' => $e['count'],
            'backup_note' => $e['note'],
        ];
    }

    /**
     * The two databases that are not tenants and still matter.
     *
     * saas_console holds every customer application and the whole audit trail;
     * saas_registry is the list of who the tenants are. Neither is covered by
     * the tenant loop above, and both are named in backup.sh's must-be-dumped
     * list - so the console watches them the same way it watches a customer.
     */
    private function systemDatabases(): void
    {
        foreach ([
            'saas_console' => config('database.connections.console.database', 'saas_console'),
            'saas_registry' => config('database.connections.registry.database', 'saas_registry'),
        ] as $label => $database) {
            TenantStat::updateOrCreate(
                ['subdomain' => $label],
                array_merge([
                    'kind' => 'system',
                    'database_name' => $database,
                    'collected_at' => now(),
                    'error' => null,
                ], self::backupColumns($database))
            );
            $this->line(sprintf('  %-16s %s', $label, 'system database'));
        }
    }
}
