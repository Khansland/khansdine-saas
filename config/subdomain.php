<?php

// The saas site is its OWN front end, not one of the produce sites. It borrows
// the shared package only for the locale plumbing and the /lang/{code} route.
return [
    // THE SETTLED PRODUCT IDENTITY. Latin script in all three languages - only
    // the line beneath it is translated (see saas.brand.product).
    //
    // This is the SaaS front end, which has no tenant, so it shows the product
    // identity itself. On a TENANT surface the rule is different and stays
    // different: there the brand block is that customer's OWN business name
    // with this product line beneath it.
    'site_name' => "Khan's Systems",
    'primary_color' => '#0f766e',
    'primary_hover' => '#115e59',
    'lang_switcher' => true,
    'locales' => [
        'bn' => 'বাংলা',
        'en' => 'English',
        'ru' => 'Русский',
    ],
    'phone' => env('SITE_PHONE', '+880 1757-585675'),
    'email' => env('SITE_EMAIL', 'noreply@khansdine.com.bd'),
    'demo_url' => env('DEMO_URL', 'https://aquademo.khansdine.com.bd'),
];
