<?php

namespace App\Console\Commands;

use App\Models\AuditEvent;
use App\Models\ConsoleUser;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * The ONE account, created from a terminal.
 *
 * There is no sign-up page and no password reset: this console can suspend and
 * delete a customer's whole install, so the only way in is an account somebody
 * with shell access made. The password is generated here unless one is given,
 * printed ONCE to the terminal that ran the command, and never stored anywhere
 * but as a hash.
 */
class CreateConsoleUserCommand extends Command
{
    protected $signature = 'console:user
        {email : the address to sign in with}
        {--name=Habib : display name}
        {--password= : use this password instead of a generated one}
        {--reset : the account already exists; set a new password}';

    protected $description = 'Create (or re-password) the console account';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('That is not an email address.');

            return self::FAILURE;
        }

        $existing = ConsoleUser::where('email', $email)->first();
        if ($existing && ! $this->option('reset')) {
            $this->error("{$email} already exists. Pass --reset to set a new password.");

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: Str::password(20));

        $user = $existing ?: new ConsoleUser(['email' => $email]);
        $user->name = (string) $this->option('name');
        $user->password = $password;
        $user->is_active = true;
        $user->save();

        AuditEvent::create([
            'actor' => 'console-command',
            'action' => $existing ? 'console_user.password_reset' : 'console_user.created',
            'subject_type' => 'console_user',
            'subject_id' => (string) $user->id,
            'detail' => ['email' => $email],
        ]);

        $this->newLine();
        $this->info($existing ? 'Password reset.' : 'Console account created.');
        $this->line('  email:    ' . $email);
        $this->line('  password: ' . $password);
        $this->newLine();
        $this->warn('This is the only time the password is shown. It is stored as a hash.');

        return self::SUCCESS;
    }
}
