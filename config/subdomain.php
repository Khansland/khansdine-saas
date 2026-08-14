<?php

// The saas site is its OWN front end, not one of the produce sites. It borrows
// the shared package only for the locale plumbing and the /lang/{code} route.
return [
    'site_name' => "Khan's Dine",
    'tagline' => 'Farm software',
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
