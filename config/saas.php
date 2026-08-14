<?php

return [
    // Where a new application is announced. His address, not a customer's.
    'notify_to' => env('SAAS_NOTIFY_TO', 'khansdinedigram@gmail.com'),

    // The deployment whose artisan runs the lifecycle verbs.
    'tenant_deployment' => env('SAAS_TENANT_DEPLOYMENT', '/home/khansdine/tenant.khansdine.com.bd'),

    'base_domain' => env('SAAS_BASE_DOMAIN', 'khansdine.com.bd'),

    // Timestamps are STORED in UTC, like everything else. They are SHOWN in the
    // box's own time, because the person reading them is standing next to the
    // box and will compare what he sees against a file listing.
    'display_timezone' => env('SAAS_DISPLAY_TZ', 'Asia/Dhaka'),
];
