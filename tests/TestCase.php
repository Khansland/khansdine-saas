<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function refreshApplication()
    {
        parent::refreshApplication();

        // A test run writes nothing into the deployment's logs (the R-0587 rule
        // the other repos carry): every channel with a path gets a null handler.
        foreach ((array) $this->app['config']->get('logging.channels') as $name => $channel) {
            if (is_array($channel) && isset($channel['path'])) {
                $this->app['config']->set("logging.channels.{$name}", [
                    'driver' => 'monolog',
                    'handler' => \Monolog\Handler\NullHandler::class,
                ]);
            }
        }

        if (($db = (string) $this->app['db']->connection()->getDatabaseName()) !== ':memory:') {
            throw new \RuntimeException("REFUSING TO RUN: the suite is pointed at '{$db}', not sqlite :memory:.");
        }

        return $this->app;
    }
}
