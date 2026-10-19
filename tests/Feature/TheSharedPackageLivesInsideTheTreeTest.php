<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * ★★ SAAS CARRIES ITS OWN COPY OF THE SHARED PACKAGE — R-0597 (AG-0088).
 *
 * saas's php-fpm pool keeps open_basedir to its own tree (no /home/khansdine/packages,
 * unlike every other khansdine pool). From 17 Oct (R-0589/R-0591) bootstrap/app.php
 * loads RedactsLoggedExceptions on every request; vendor/khansdine/subdomain-shared was
 * a symlink into the packages directory, so every web request hit an open_basedir
 * warning and answered 500 - while the CLI, which has no open_basedir, passed every test.
 *
 * composer.json's path repository now says "symlink": false, so the package is a real
 * directory inside the tree. The second test reproduces the pool's restriction in a
 * fresh PHP process (the pool's own open_basedir, re-rooted at this tree) and handles
 * a request through the kernel there: the check the CLI suite could not make before.
 */
class TheSharedPackageLivesInsideTheTreeTest extends TestCase
{
    private const POOL = '/etc/php/8.3/fpm/pool.d/saas.khansdine.com.bd.conf';
    private const LIVE = '/home/khansdine/saas.khansdine.com.bd';

    public function test_the_package_is_a_real_directory_inside_the_tree(): void
    {
        $dir = base_path('vendor/khansdine/subdomain-shared');
        $this->assertDirectoryExists($dir);
        $this->assertFalse(is_link($dir), 'vendor/khansdine/subdomain-shared is a symlink: the pool cannot read through it');

        $file = (new \ReflectionClass(\Khansdine\SubdomainShared\Logging\RedactsLoggedExceptions::class))->getFileName();
        $this->assertStringStartsWith(realpath(base_path()) . '/', $file, 'the class is loaded from outside the tree');
    }

    public function test_a_request_is_handled_under_the_pools_open_basedir(): void
    {
        $basedir = $this->poolBasedir();
        $this->assertStringStartsWith(realpath(base_path()) . ':', $basedir);

        $script = tempnam(sys_get_temp_dir(), 'r0597-ob');
        file_put_contents($script, <<<'PHP'
<?php
require $argv[1] . '/vendor/autoload.php';
$app = require $argv[1] . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::create('/up'));
class_exists(Khansdine\SubdomainShared\Logging\RedactsLoggedExceptions::class) or exit(3);
echo 'STATUS=' . $response->getStatusCode();
PHP);
        $env = 'APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: LOG_CHANNEL=null CACHE_STORE=array SESSION_DRIVER=array';
        $out = shell_exec('env ' . $env . ' ' . escapeshellarg(PHP_BINARY)
            . ' -d ' . escapeshellarg('open_basedir=' . $basedir . ':' . dirname($script))
            . ' -d display_errors=stderr ' . escapeshellarg($script) . ' ' . escapeshellarg(realpath(base_path())) . ' 2>&1');
        @unlink($script);

        $this->assertStringNotContainsString('open_basedir', (string) $out, 'the pool would refuse a file the request needs');
        $this->assertStringContainsString('STATUS=200', (string) $out, 'the request was not answered: ' . substr((string) $out, 0, 400));
    }

    /** The pool's open_basedir with the live tree swapped for this one (worktree or live). */
    private function poolBasedir(): string
    {
        $value = self::LIVE . ':/tmp:/usr/share/php:/dev/urandom';
        if (is_readable(self::POOL) && preg_match('/^\s*php_admin_value\[open_basedir\]\s*=\s*(\S+)/m', file_get_contents(self::POOL), $m)) {
            $value = $m[1];
        }

        return str_replace(self::LIVE, realpath(base_path()), $value);
    }
}
