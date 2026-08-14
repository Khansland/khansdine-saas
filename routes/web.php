<?php

use App\Http\Controllers\Console\ApplicationController;
use App\Http\Controllers\Console\AuditController;
use App\Http\Controllers\Console\AuthController;
use App\Http\Controllers\Console\TenantController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

/*
 * THE LANGUAGE SWITCHER.
 *
 * This was the shared package's route. It is ten lines, and taking them means
 * this app loads nothing from outside its own directory - which is what its
 * php-fpm open_basedir already said, and what did not survive contact with a
 * vendor symlink into /home/khansdine/packages. The choice is recorded IN THE
 * SESSION as well as in the domain-wide cookie, so a choice made here outranks
 * a stale one made on a sibling site.
 */
Route::get('/lang/{locale}', function (string $locale) {
    if (! in_array($locale, \App\Http\Middleware\SetLocale::SUPPORTED, true)) {
        return back();
    }

    session(['kd_locale_chosen' => $locale]);

    return back()->withCookie(cookie('kd_locale', $locale, 525600, '/', null, true, false));
})->name('lang.switch');

/*
 * THE PUBLIC SIDE. Indexed on purpose - see public/robots.txt.
 */
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/apply', [PublicController::class, 'form'])->name('apply');
Route::post('/apply', [PublicController::class, 'submit'])->name('apply.submit');
Route::get('/apply/thanks', [PublicController::class, 'thanks'])->name('apply.thanks');

/*
 * THE CONSOLE. Its own login, its own guard, no SSO.
 */
Route::prefix('admin')->name('console.')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::middleware('console.auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', fn () => redirect()->route('console.tenants'))->name('home');

        Route::get('/tenants', [TenantController::class, 'index'])->name('tenants');
        Route::get('/tenants/{subdomain}', [TenantController::class, 'show'])->name('tenants.show');
        // GET, because it RENDERS a command. Nothing is executed, so there is
        // nothing here for a CSRF-less POST to trigger.
        Route::get('/tenants/{subdomain}/{verb}', [TenantController::class, 'command'])
            ->name('tenants.command');

        Route::get('/applications', [ApplicationController::class, 'index'])->name('applications');
        Route::get('/applications/{application}', [ApplicationController::class, 'show'])
            ->name('applications.show');
        Route::post('/applications/{application}', [ApplicationController::class, 'update'])
            ->name('applications.update');
        Route::get('/applications/{application}/provision', [ApplicationController::class, 'provision'])
            ->name('applications.provision');
        Route::post('/applications/{application}/provision', [ApplicationController::class, 'provisionCommand'])
            ->name('applications.provision.command');

        Route::get('/audit', [AuditController::class, 'index'])->name('audit');
    });
});
