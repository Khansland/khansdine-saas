<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        return view('console.audit', [
            'events' => AuditEvent::query()
                ->when($request->query('action'), fn ($q, $a) => $q->where('action', 'like', $a . '%'))
                ->latest()->paginate(60)->withQueryString(),
            'actions' => AuditEvent::select('action')->distinct()->orderBy('action')->pluck('action'),
            'action' => $request->query('action'),
        ]);
    }
}
