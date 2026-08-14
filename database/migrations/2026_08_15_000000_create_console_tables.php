<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('console_users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_login_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });

        Schema::create('applications', function (Blueprint $t) {
            $t->id();
            // What a farmer fills in. Phone is the one that matters and the one
            // Habib will actually use, so it is required and indexed.
            $t->string('farm_name');
            $t->string('owner_name');
            $t->string('phone', 32)->index();
            $t->string('district')->nullable();
            $t->unsignedInteger('pond_count')->nullable();
            $t->string('species')->nullable();
            $t->json('bundles')->nullable();
            $t->text('note')->nullable();
            $t->string('locale', 5)->default('bn');

            // What the console adds afterwards.
            $t->string('status', 20)->default('new')->index();
            $t->text('admin_note')->nullable();
            $t->string('proposed_subdomain', 63)->nullable();
            $t->timestamp('contacted_at')->nullable();
            $t->timestamp('decided_at')->nullable();

            // Spam forensics without keeping an address in the clear.
            $t->string('ip_hash', 64)->nullable()->index();
            $t->string('user_agent', 255)->nullable();
            $t->timestamps();
        });

        Schema::create('audit_events', function (Blueprint $t) {
            $t->id();
            $t->string('actor')->index();
            $t->string('action')->index();
            $t->string('subject_type')->nullable();
            $t->string('subject_id')->nullable();
            $t->json('detail')->nullable();
            $t->string('ip_hash', 64)->nullable();
            $t->timestamps();
            $t->index(['subject_type', 'subject_id']);
        });

        Schema::create('tenant_stats', function (Blueprint $t) {
            $t->id();
            $t->string('subdomain', 63)->unique();
            $t->string('database_name');
            $t->unsignedInteger('tanks')->nullable();
            $t->unsignedInteger('batches')->nullable();
            $t->unsignedInteger('users')->nullable();
            $t->unsignedBigInteger('db_bytes')->nullable();
            $t->timestamp('last_backup_at')->nullable();
            $t->timestamp('collected_at')->nullable();
            $t->string('error')->nullable();
            $t->timestamps();
        });

        Schema::create('cache', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->mediumText('value');
            $t->integer('expiration');
        });

        // The rate limiter behind the application form stores its counters here,
        // so a restart does not hand a spammer a fresh allowance.
        Schema::create('cache_locks', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->string('owner');
            $t->integer('expiration');
        });
    }

    public function down(): void
    {
        foreach (['cache_locks', 'cache', 'tenant_stats', 'audit_events',
                  'applications', 'sessions', 'console_users'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
