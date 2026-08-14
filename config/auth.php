<?php

/*
 * ONE login, and it is Habib's own.
 *
 * Deliberately NOT the .khansdine SSO family and not a tenant's user table:
 * the console can suspend and delete a customer's whole install, so the account
 * that reaches it must not be reachable by anything a customer or an estate
 * user can sign in with. The guard is named 'console', the provider points at
 * this app's own table, and there is no second guard to fall back to.
 */
return [

    'defaults' => [
        'guard' => 'console',
        'passwords' => 'console',
    ],

    'guards' => [
        'console' => [
            'driver' => 'session',
            'provider' => 'console_users',
        ],
    ],

    'providers' => [
        'console_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\ConsoleUser::class,
        ],
    ],

    // No self-service reset: one account, created from the console command line.
    // A password-reset flow on a page that can delete a customer's database is
    // an attack surface with no user to justify it.
    'passwords' => [],

    'password_timeout' => 1800,
];
