<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

class ConsoleUser extends Authenticatable
{
    protected $table = 'console_users';

    protected $fillable = ['name', 'email', 'password', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'last_login_at' => 'datetime'];
    }

    public function setPasswordAttribute(string $value): void
    {
        // Hash on the way in, so no call site can store a plaintext password by
        // forgetting to. The console command that creates the account passes
        // the plaintext exactly once and never keeps it.
        $this->attributes['password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
    }
}
