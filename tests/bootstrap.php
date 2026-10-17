<?php

// R-0589: saas's composer.json has no autoload-dev, and dependencies do not change
// without approval, so the test namespace is mapped here instead of in composer.
require __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'Tests\\')) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 6)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
