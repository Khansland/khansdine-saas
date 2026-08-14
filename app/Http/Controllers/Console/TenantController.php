<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\TenantStat;
use App\Services\Lifecycle;
use App\Services\Registry;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index()
    {
        $healthy = Registry::healthy();
        $tenants = $healthy ? Registry::tenants() : collect();
        $stats = TenantStat::get()->keyBy('subdomain');

        return view('console.tenants', [
            'healthy' => $healthy,
            'tenants' => $tenants,
            'stats' => $stats,
        ]);
    }

    public function show(string $subdomain)
    {
        $tenant = Registry::find($subdomain);
        abort_if(! $tenant, 404);

        return view('console.tenant', [
            'tenant' => $tenant,
            'stat' => TenantStat::where('subdomain', $subdomain)->first(),
            'verbs' => Lifecycle::availableFor($tenant->status ?? null),
            'audit' => AuditEvent::where('subject_type', 'tenant')
                ->where('subject_id', $subdomain)
                ->latest()->limit(20)->get(),
            'siteUrl' => 'https://' . $subdomain . '.khansdine.com.bd',
        ]);
    }

    /**
     * Show the command for a verb - and record that it was asked for.
     *
     * The console never runs it. What it does is remove every chance to mistype
     * it, and leave a line in the audit trail saying who asked for what and
     * when, so "who suspended them?" has an answer even though the suspending
     * happened in a terminal.
     */
    public function command(Request $request, string $subdomain, string $verb)
    {
        $tenant = Registry::find($subdomain);
        abort_if(! $tenant, 404);
        abort_if(! isset(Lifecycle::VERBS[$verb]), 404);

        $options = array_filter([
            'reason' => $request->input('reason'),
            'business-name' => $request->input('business_name'),
            'admin-email' => $request->input('admin_email'),
        ], fn ($v) => filled($v));

        $allowed = Lifecycle::VERBS[$verb]['options'] ?? [];
        $options = array_intersect_key($options, array_flip($allowed));

        $line = Lifecycle::line($verb, $subdomain, $options);

        AuditEvent::record('tenant.' . $verb . '.command_shown', 'tenant', $subdomain, [
            'status_at_the_time' => $tenant->status ?? null,
            'options' => array_keys($options),
        ]);

        return view('console.command', [
            'tenant' => $tenant,
            'verb' => $verb,
            'line' => $line,
            'destructive' => Lifecycle::VERBS[$verb]['destructive'],
            'guards' => $verb === 'delete' ? Lifecycle::DELETE_GUARDS : [],
        ]);
    }
}
