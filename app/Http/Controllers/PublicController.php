<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\AuditEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class PublicController extends Controller
{
    /** The bundles as they stand. No prices: Habib has not set them. */
    public const BUNDLES = ['core', 'people', 'aimama', 'sales'];

    public function home()
    {
        return view('public.home', ['bundles' => self::BUNDLES]);
    }

    public function form()
    {
        return view('public.apply', ['bundles' => self::BUNDLES]);
    }

    public function submit(Request $request)
    {
        // ── SPAM CONTROL, WITHOUT A THIRD PARTY ───────────────────────────
        // A honeypot and a rate limit are proportionate at this volume, and
        // neither sends a farmer's details to somebody else's service to be
        // judged. The honeypot field is named like a real one and hidden with
        // CSS; a browser leaves it empty and a bot fills it.
        if (trim((string) $request->input('company_website')) !== '') {
            // Answer as if it worked. A bot that is told it failed comes back.
            return redirect()->route('apply.thanks');
        }

        $key = 'apply:' . AuditEvent::hashIp($request->ip());
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return back()->withInput()->withErrors([
                'phone' => __('saas.apply.too_many', ['minutes' => ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }
        RateLimiter::hit($key, 3600);

        $data = $request->validate([
            'farm_name' => 'required|string|max:120',
            'owner_name' => 'required|string|max:120',
            // Phone first and phone required: it is how a farm is actually
            // reached here, and an email address is not asked for at all.
            'phone' => 'required|string|min:6|max:32',
            'district' => 'nullable|string|max:80',
            'pond_count' => 'nullable|integer|min:0|max:10000',
            'species' => 'nullable|string|max:160',
            'bundles' => 'nullable|array',
            'bundles.*' => 'in:' . implode(',', self::BUNDLES),
            'note' => 'nullable|string|max:2000',
        ]);

        $application = Application::create($data + [
            'locale' => app()->getLocale(),
            'status' => 'new',
            'ip_hash' => AuditEvent::hashIp($request->ip()),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        AuditEvent::record('application.submitted', 'application', $application->id, [
            'farm_name' => $application->farm_name,
            'locale' => $application->locale,
        ]);

        $this->notifyOwner($application);

        return redirect()->route('apply.thanks');
    }

    public function thanks()
    {
        return view('public.thanks');
    }

    /**
     * Tell Habib, and tell nobody else.
     *
     * No automatic mail goes to the applicant: he answers by hand, and a farm
     * that gets a machine-written reply before a human one learns that nobody
     * read it. Sent through the estate's own Postfix identity, which is already
     * DKIM-signed, so it does not land in his spam folder.
     */
    private function notifyOwner(Application $a): void
    {
        $to = config('saas.notify_to');
        if (! $to) {
            return;
        }

        $lines = [
            'A new application came in at ' . config('app.url') . '/apply',
            '',
            'Farm:     ' . $a->farm_name,
            'Owner:    ' . $a->owner_name,
            'Phone:    ' . $a->phone,
            'District: ' . ($a->district ?: '-'),
            'Ponds:    ' . ($a->pond_count ?? '-'),
            'Species:  ' . ($a->species ?: '-'),
            'Bundles:  ' . (empty($a->bundles) ? '-' : implode(', ', $a->bundles)),
            'Language: ' . $a->locale,
            '',
            'Note:',
            trim((string) $a->note) !== '' ? $a->note : '(none)',
            '',
            'Open it: ' . route('console.applications.show', $a->id),
        ];

        try {
            Mail::raw(implode("\n", $lines), function ($m) use ($to, $a) {
                $m->to($to)->subject('New farm application: ' . $a->farm_name);
            });
        } catch (\Throwable $e) {
            // A mail failure must not lose the application - it is already
            // stored, and the inbox is the record that matters.
            report($e);
        }
    }
}
