<?php

/**
 * ★ THE ONE LIST OF WHAT SHOULD BE UP.
 *
 * Report site-uptime-watch (2026-08-16). Seven customer-facing storefronts
 * were dead for two days. Part of why nobody noticed is that nobody had a
 * list: there was no single place that said what this box is supposed to be
 * serving, so there was nothing to compare reality against.
 *
 * THIS IS THAT PLACE. A site that is not in this file is not watched. Adding
 * a deployment means adding a line here, and nowhere else.
 *
 * ── EACH ENTRY ────────────────────────────────────────────────────────────
 *   key        short name, shown on the console
 *   url        what a visitor types
 *   expect     the status THIS entry is supposed to return. hisab redirects to
 *              a login by design, so 302 is its health and 200 would be the
 *              surprise. Never assume 200.
 *   min_bytes  the body floor. A 200 is not proof of health — the seven dead
 *              storefronts each answered with a valid HTTP response carrying a
 *              6,592-byte Laravel error page. Floors are set well under each
 *              site's real size and well over that error page.
 *   origin     true to ALSO ask the origin directly, bypassing Cloudflare.
 *              "edge fine, origin broken" and "origin fine, edge broken" are
 *              different emergencies and one number cannot say which.
 *   note       what this entry is, for the person reading the screen
 */
return [
    // How long to wait for one site before giving up. Short on purpose: the
    // check must never be the thing that takes a box down, and a site that
    // needs more than this to answer its front page is already a problem.
    'timeout' => (int) env('SITE_CHECK_TIMEOUT', 10),
    'connect_timeout' => (int) env('SITE_CHECK_CONNECT_TIMEOUT', 5),

    // The loopback address the origin-direct probes resolve to, so they reach
    // this box's nginx instead of travelling out to the edge and back.
    'origin_ip' => env('SITE_CHECK_ORIGIN_IP', '127.0.0.1'),

    'entries' => [
        // ── the two farm apps: storefront AND the admin behind it ─────────
        // A storefront can be 200 while the admin is 500 — they are different
        // route groups with different middleware, and the admin is the half
        // Habib actually works in.
        ['key' => 'aquaculture', 'url' => 'https://aquaculture.khansdine.com.bd/',
         'expect' => 200, 'min_bytes' => 40000, 'origin' => true, 'note' => 'his farm — storefront'],
        ['key' => 'aquaculture-admin', 'url' => 'https://aquaculture.khansdine.com.bd/login',
         'expect' => 200, 'min_bytes' => 20000, 'origin' => true, 'note' => 'his farm — admin login page'],
        ['key' => 'aquademo', 'url' => 'https://aquademo.khansdine.com.bd/',
         'expect' => 200, 'min_bytes' => 20000, 'origin' => true, 'note' => 'the public demo — storefront'],
        ['key' => 'aquademo-admin', 'url' => 'https://aquademo.khansdine.com.bd/login',
         'expect' => 200, 'min_bytes' => 4000, 'origin' => true, 'note' => 'the public demo — sign-in page'],

        // ── the rest of the eleven that share the storefront layout ───────
        ['key' => 'hisab', 'url' => 'https://hisab.khansdine.com.bd/',
         'expect' => 302, 'min_bytes' => 0, 'origin' => true, 'note' => 'the books — redirects to its login by design'],
        ['key' => 'saas', 'url' => 'https://saas.khansdine.com.bd/',
         'expect' => 200, 'min_bytes' => 5000, 'origin' => true, 'note' => 'this console'],
        ['key' => 'agriculture', 'url' => 'https://agriculture.khansdine.com.bd/',
         'expect' => 200, 'min_bytes' => 30000, 'origin' => true, 'note' => 'spoke — was down 14-16 Aug'],
        ['key' => 'animalfeeds', 'url' => 'https://animalfeeds.khansdine.com.bd/',
         'expect' => 200, 'min_bytes' => 30000, 'origin' => true, 'note' => 'spoke — was down 14-16 Aug'],
        ['key' => 'compost', 'url' => 'https://compost.khansdine.com.bd/',
         'expect' => 200, 'min_bytes' => 30000, 'origin' => true, 'note' => 'spoke — was down 14-16 Aug'],
        ['key' => 'feeds', 'url' => 'https://feeds.khansdine.com.bd/',
         'expect' => 200, 'min_bytes' => 30000, 'origin' => true, 'note' => 'spoke — was down 14-16 Aug'],
        ['key' => 'fishr', 'url' => 'https://fishr.khansdine.com.bd/',
         'expect' => 200, 'min_bytes' => 30000, 'origin' => true, 'note' => 'spoke — was down 14-16 Aug'],
        ['key' => 'parking', 'url' => 'https://parking.khansdine.com.bd/',
         'expect' => 200, 'min_bytes' => 30000, 'origin' => true, 'note' => 'spoke — was down 14-16 Aug'],
        ['key' => 'picnic', 'url' => 'https://picnic.khansdine.com.bd/',
         'expect' => 200, 'min_bytes' => 30000, 'origin' => true, 'note' => 'spoke — was down 14-16 Aug'],

        // ── the two that are not on the shared layout but are his ─────────
        ['key' => 'khansland', 'url' => 'https://khansland.com.bd/',
         'expect' => 200, 'min_bytes' => 5000, 'origin' => true, 'note' => 'the parent site'],
        ['key' => 'dashtv', 'url' => 'https://dashtv.ru/',
         'expect' => 200, 'min_bytes' => 5000, 'origin' => true, 'note' => 'dashtv.ru'],
    ],
];
