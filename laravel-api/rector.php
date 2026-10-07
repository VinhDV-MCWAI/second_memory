<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelLevelSetList;

// Dry-run by default (`composer rector`); apply with `vendor/bin/rector process`.
return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withCache(__DIR__.'/storage/framework/cache/rector')
    ->withPhpSets(php83: true)
    ->withSets([LaravelLevelSetList::UP_TO_LARAVEL_130_WITHOUT_ATTRIBUTES]);
