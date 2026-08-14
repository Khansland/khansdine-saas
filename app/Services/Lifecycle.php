<?php

namespace App\Services;

/**
 * THE FIVE LIFECYCLE VERBS, AND WHY THIS CLASS RENDERS RATHER THAN RUNS.
 *
 * provision / suspend / resume / export / delete already exist as artisan
 * commands in the tenant deployment. They open a MySQL account that can create
 * and drop databases, and that account is read from a file outside every
 * deployment at the moment a CONSOLE command asks for it - Provisioner refuses
 * outright if app()->runningInConsole() is false.
 *
 * So a button cannot run them, and this class does not try. It builds the exact
 * command line and the console shows it to be copied and run in a terminal.
 * The alternative - a queued job, which would also satisfy runningInConsole()
 * because a worker is a console process - was considered and rejected for now:
 * it would put "create and drop customer databases" behind a web session, and
 * one operator approving one application at a time does not need that. When
 * volume justifies a worker, the change is to dispatch these same commands as
 * jobs; the argument list below does not change.
 *
 * ONE IMPLEMENTATION: the console never re-implements a verb. What it renders
 * is the command that already exists.
 */
class Lifecycle
{
    public const DEPLOYMENT = '/home/khansdine/tenant.khansdine.com.bd';

    /**
     * The options each verb actually accepts, copied from the command signatures
     * so the console cannot render a flag the command would reject.
     */
    public const VERBS = [
        'provision' => ['command' => 'saas:provision', 'destructive' => false,
            'options' => ['business-name', 'admin-email', 'admin-name', 'database', 'no-invite']],
        'suspend' => ['command' => 'saas:suspend', 'destructive' => false, 'options' => ['reason']],
        'resume' => ['command' => 'saas:resume', 'destructive' => false, 'options' => []],
        'export' => ['command' => 'saas:export', 'destructive' => false, 'options' => ['dir']],
        'delete' => ['command' => 'saas:delete', 'destructive' => true, 'options' => ['confirm', 'keep-database']],
    ];

    /**
     * The command line for a verb, ready to paste.
     *
     * @param  array<string,string>  $options
     */
    public static function line(string $verb, string $subdomain, array $options = []): string
    {
        if (! isset(self::VERBS[$verb])) {
            throw new \InvalidArgumentException("Unknown lifecycle verb: {$verb}");
        }

        $parts = [
            'sudo -u khansdine sh -c "cd ' . self::DEPLOYMENT,
            '&& php artisan ' . self::VERBS[$verb]['command'],
            escapeshellarg($subdomain),
        ];

        $allowed = self::VERBS[$verb]['options'] ?? [];
        foreach ($options as $name => $value) {
            if (! in_array($name, $allowed, true)) {
                throw new \InvalidArgumentException("saas:{$verb} has no --{$name} option.");
            }
            $parts[] = $value === true || $value === ''
                ? '--' . $name
                : '--' . $name . '=' . escapeshellarg((string) $value);
        }

        return implode(' ', $parts) . '"';
    }

    /**
     * Which verbs make sense for a tenant in this state.
     *
     * A suspended tenant is resumed, not suspended again; a tenant that never
     * finished provisioning is not exported. Offering an action that will be
     * refused three seconds later teaches an operator to ignore the screen.
     */
    public static function availableFor(?string $status): array
    {
        return match ($status) {
            'active' => ['suspend', 'export', 'delete'],
            'suspended' => ['resume', 'export', 'delete'],
            'provisioning' => ['provision', 'delete'],
            default => ['provision'],
        };
    }

    /**
     * The guards on delete, stated on screen rather than only in the command.
     *
     * The command itself enforces both - an export on disk AND the subdomain
     * typed back. The console repeats them so an operator knows before running
     * it that a missing export means the command will stop, not that it will
     * ask nicely.
     */
    public const DELETE_GUARDS = [
        'An export marker AND its dump file must already exist on disk. The command '
        . 'refuses outright if the tenant has never been exported, and refuses again '
        . 'if the marker is there but the dump is gone.',
        'The subdomain must be typed back in full - interactively, or through --confirm.',
    ];
}
