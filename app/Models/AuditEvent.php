<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditEvent extends Model
{
    protected $fillable = ['actor', 'action', 'subject_type', 'subject_id', 'detail', 'ip_hash'];

    protected function casts(): array
    {
        return ['detail' => 'array'];
    }

    /**
     * Who, when, what - written for every action the console takes.
     *
     * The IP is stored as a HASH. The console is a one-person tool, so the
     * address proves nothing about identity that the actor column does not
     * already say, and a plaintext address in a table is a liability with no
     * reader.
     */
    public static function record(string $action, ?string $subjectType = null, $subjectId = null, array $detail = []): self
    {
        return self::create([
            'actor' => Auth::user()?->email ?? 'console',
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId !== null ? (string) $subjectId : null,
            'detail' => $detail,
            'ip_hash' => self::hashIp(request()?->ip()),
        ]);
    }

    public static function hashIp(?string $ip): ?string
    {
        return $ip ? substr(hash('sha256', $ip . config('app.key')), 0, 32) : null;
    }
}
