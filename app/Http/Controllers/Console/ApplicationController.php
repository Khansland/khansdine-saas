<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditEvent;
use App\Services\Lifecycle;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        return view('console.applications', [
            'status' => in_array($status, Application::STATUSES, true) ? $status : null,
            'applications' => Application::query()
                ->when(in_array($status, Application::STATUSES, true), fn ($q) => $q->where('status', $status))
                ->latest()->paginate(30)->withQueryString(),
            'counts' => Application::selectRaw('status, COUNT(*) as n')->groupBy('status')
                ->pluck('n', 'status'),
        ]);
    }

    public function show(Application $application)
    {
        return view('console.application', [
            'a' => $application,
            'audit' => AuditEvent::where('subject_type', 'application')
                ->where('subject_id', (string) $application->id)
                ->latest()->limit(20)->get(),
            // Approving PRE-FILLS. It does not fire anything: the operator still
            // runs the command, and can change every value first.
            'suggested' => $this->suggestSubdomain($application),
        ]);
    }

    public function update(Request $request, Application $application)
    {
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', Application::STATUSES),
            'admin_note' => 'nullable|string|max:4000',
            'proposed_subdomain' => 'nullable|string|max:63|regex:/^[a-z0-9][a-z0-9-]*$/',
        ]);

        $was = $application->status;
        $application->fill($data);

        if ($data['status'] === 'contacted' && ! $application->contacted_at) {
            $application->contacted_at = now();
        }
        if (in_array($data['status'], ['approved', 'rejected'], true)) {
            $application->decided_at = now();
        }
        $application->save();

        AuditEvent::record('application.updated', 'application', $application->id, [
            'from' => $was, 'to' => $application->status,
        ]);

        // APPROVED does not provision. It fills the form and hands over the
        // command; a person still has to run it in a terminal.
        if ($application->status === 'approved') {
            return redirect()->route('console.applications.provision', $application->id);
        }

        return redirect()->route('console.applications.show', $application->id)
            ->with('ok', __('saas.console.saved'));
    }

    /**
     * The provisioning form, PRE-FILLED from the application.
     *
     * Every value is editable, nothing is submitted anywhere, and the page ends
     * in a command line to copy. That is the whole mechanism: the console knows
     * what to run, the operator decides whether to run it.
     */
    public function provision(Application $application)
    {
        return view('console.provision', [
            'a' => $application,
            'suggested' => $application->proposed_subdomain ?: $this->suggestSubdomain($application),
            'line' => null,
        ]);
    }

    public function provisionCommand(Request $request, Application $application)
    {
        $data = $request->validate([
            'subdomain' => 'required|string|max:63|regex:/^[a-z0-9][a-z0-9-]*$/',
            'business_name' => 'nullable|string|max:120',
            'admin_email' => 'nullable|email|max:160',
        ]);

        $line = Lifecycle::line('provision', $data['subdomain'], array_filter([
            'business-name' => $data['business_name'] ?? null,
            'admin-email' => $data['admin_email'] ?? null,
        ], fn ($v) => filled($v)));

        $application->forceFill(['proposed_subdomain' => $data['subdomain']])->save();

        AuditEvent::record('application.provision_command_shown', 'application', $application->id, [
            'subdomain' => $data['subdomain'],
        ]);

        return view('console.provision', [
            'a' => $application,
            'suggested' => $data['subdomain'],
            'line' => $line,
        ]);
    }

    /**
     * A first guess at a subdomain, from the farm name.
     *
     * A guess, shown in an editable box - never applied. Two farms called
     * "Rahman Fish" would collide, and the command refuses a name that is taken
     * or reserved, which is where that is caught.
     */
    private function suggestSubdomain(Application $a): string
    {
        $slug = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '', $a->farm_name));

        return substr($slug, 0, 20) ?: 'farm';
    }
}
