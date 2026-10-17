<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // R-0591: every log line strips values (SQL bindings, e-mail addresses, BD
        // mobiles), however it was built. The shared package registers this in every
        // deployment that discovers it; saas lists it in dont-discover, so it is here.
        \Khansdine\SubdomainShared\Logging\RedactsLogRecords::register($this->app);
    }

    public function boot(): void
    {
        // Behind nginx the app only ever sees http; every link it writes must
        // still be https, or the console posts a login form over plain text.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
