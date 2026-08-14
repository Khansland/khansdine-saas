<?php

return [
    // Where a new application is announced. His address, not a customer's.
    'notify_to' => env('SAAS_NOTIFY_TO', 'khansdinedigram@gmail.com'),

    // The deployment whose artisan runs the lifecycle verbs.
    'tenant_deployment' => env('SAAS_TENANT_DEPLOYMENT', '/home/khansdine/tenant.khansdine.com.bd'),

    'base_domain' => env('SAAS_BASE_DOMAIN', 'khansdine.com.bd'),
];
