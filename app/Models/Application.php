<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    /** The lifecycle of an enquiry. provisioned is the END, and only a human sets it. */
    public const STATUSES = ['new', 'contacted', 'approved', 'rejected', 'provisioned'];

    protected $fillable = [
        'farm_name', 'owner_name', 'phone', 'district', 'pond_count',
        'species', 'bundles', 'note', 'locale', 'status', 'admin_note',
        'proposed_subdomain', 'ip_hash', 'user_agent',
    ];

    protected function casts(): array
    {
        return ['bundles' => 'array', 'contacted_at' => 'datetime', 'decided_at' => 'datetime'];
    }
}
