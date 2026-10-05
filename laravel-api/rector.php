<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelLevelSetList;

// Dry-run only for now (`composer rector`). Rules are applied deliberately,
// one set at a time, in the Laravel upgrade item (PLAN U1).
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
    ->withPhpSets(php82: true)
    ->withSets([LaravelLevelSetList::UP_TO_LARAVEL_110]);
