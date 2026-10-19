<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Tests\TestCase;

/**
 * ★ THE SMOKE TEST THIS SITE NEVER HAD — R-0597 (brief AG-0088 E1).
 *
 * Until R-0597 this repository had no pre-commit hook and no test but Laravel's
 * skeleton example, so nothing stood between a commit and the live site. Now the
 * hook runs this suite, and this file is its floor: the app boots, every web route
 * resolves to code that exists, every GET page answers without a server error, and
 * the home page renders 200 - all in the test kernel, with hisab's API faked.
 */
class TheSiteBootsAndEveryRouteResolvesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // saas reads no API on its pages; a request that leaves the box is a failure here.
        \Illuminate\Support\Facades\Http::preventStrayRequests();
    }

    /** @return list<Route> */
    private function webRoutes(): array
    {
        return array_values(array_filter(app('router')->getRoutes()->getRoutes(),
            fn (Route $r) => ! str_starts_with($r->uri(), '_') && ! in_array($r->uri(), ['up', 'storage/{path}'], true)));
    }

    public function test_the_home_page_renders(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_every_route_names_a_controller_method_that_exists(): void
    {
        $routes = $this->webRoutes();
        $this->assertNotEmpty($routes, 'no routes loaded: the loop below would prove nothing');
        foreach ($routes as $r) {
            $uses = $r->getAction('uses');
            if ($uses instanceof \Closure) {
                continue;
            }
            [$class, $method] = array_pad(explode('@', (string) $uses), 2, '__invoke');
            $this->assertTrue(class_exists($class), "/{$r->uri()} names a class that does not exist: {$class}");
            $this->assertTrue(method_exists($class, $method), "/{$r->uri()} names a method that does not exist: {$class}@{$method}");
        }
    }

    public function test_every_get_page_answers_without_a_server_error(): void
    {
        $asked = 0;
        foreach ($this->webRoutes() as $r) {
            // health/watchman answers 503 BY DESIGN when its heartbeats are absent (a test has none):
            // that is the dead-man's switch working, not a server error.
            if (! in_array('GET', $r->methods(), true) || str_contains($r->uri(), '{') || $r->uri() === 'health/watchman') {
                continue;
            }
            $status = $this->get('/' . ltrim($r->uri(), '/'))->getStatusCode();
            $this->assertLessThan(500, $status, "GET /{$r->uri()} answered {$status}");
            $asked++;
        }
        $this->assertGreaterThan(0, $asked, 'no page was asked');
    }
}
