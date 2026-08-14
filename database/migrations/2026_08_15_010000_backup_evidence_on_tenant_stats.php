<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The backup column was a date or a blank, and a blank meant three different
 * things: nobody has looked, somebody looked and found nothing, and somebody
 * looked and could not see. Those need to be told apart on the screen, so they
 * have to be told apart in the table first.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_stats', function (Blueprint $t) {
            // 'tenant' or 'system'. saas_console and saas_registry are not
            // tenants but they hold every application and the audit trail, so
            // they are watched here too.
            $t->string('kind', 12)->default('tenant')->after('id')->index();

            // ok | none_found | cannot_look. Never null once a probe has run;
            // a row that has never been probed simply does not exist yet, and
            // the screen says THAT rather than pretending it found nothing.
            $t->string('backup_state', 16)->nullable()->after('last_backup_at');
            $t->string('backup_file', 191)->nullable()->after('backup_state');
            $t->unsignedBigInteger('backup_bytes')->nullable()->after('backup_file');
            $t->unsignedInteger('backup_count')->nullable()->after('backup_bytes');
            $t->string('backup_note', 191)->nullable()->after('backup_count');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_stats', function (Blueprint $t) {
            $t->dropColumn(['kind', 'backup_state', 'backup_file', 'backup_bytes',
                            'backup_count', 'backup_note']);
        });
    }
};
