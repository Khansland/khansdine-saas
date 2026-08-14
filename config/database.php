<?php

return [

    /*
     * The console's OWN database, and nothing else.
     *
     * saas_console holds the applications, the audit trail, Habib's one login
     * and the cached tenant statistics. Its MySQL user is granted that schema
     * and no other: not a tenant database, not hisab_khansdine, not even the
     * registry. That is the property E.1 proves, and it is enforced by GRANT,
     * not by this file.
     */
    'default' => env('DB_CONNECTION', 'console'),

    'connections' => [

        'console' => [
            'driver' => env('DB_DRIVER', 'mysql'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'saas_console'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => env('DB_PREFIX', ''),
            'strict' => true,
            'engine' => null,
        ],

        /*
         * THE TENANT REGISTRY — read-only, by grant.
         *
         * The console LISTS tenants; it never writes one. Creating, suspending
         * and deleting a tenant is console-command work with privileged
         * credentials that live outside every deployment, and a web request
         * has no business holding them. Binding this to saas_registry_ro means
         * a defect in this app cannot change a tenant's status by accident.
         */
        'registry' => [
            'driver' => env('REGISTRY_DB_DRIVER', 'mysql'),
            'host' => env('REGISTRY_DB_HOST', '127.0.0.1'),
            'port' => env('REGISTRY_DB_PORT', '3306'),
            'database' => env('REGISTRY_DB_DATABASE', 'saas_registry'),
            'username' => env('REGISTRY_DB_USERNAME'),
            'password' => env('REGISTRY_DB_PASSWORD'),
            'unix_socket' => env('REGISTRY_DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => ['client' => 'phpredis', 'default' => ['url' => env('REDIS_URL')]],
];
