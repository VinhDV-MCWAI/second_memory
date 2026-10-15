<?php

declare(strict_types=1);

// Loaded with -d auto_prepend_file by perf/profile.sh only (PERF-01). Appends one line per request
// to PERF_TIMING_LOG: URI (+ " spa" when the SPA Referer makes it stateful), PHP wall time, time
// inside database calls (the first call includes opening the connection) and PHP files loaded.

$perfTimingStart = hrtime(true);

register_shutdown_function(static function () use ($perfTimingStart): void {
    $dbMs = 0.0;
    if (function_exists('app') && app()->resolved('db')) {
        foreach (app('db')->getConnections() as $connection) {
            $dbMs += $connection->totalQueryDuration();
        }
    }
    file_put_contents(
        (string) getenv('PERF_TIMING_LOG'),
        sprintf("%s\t%.3f\t%.3f\t%d\n", ($_SERVER['REQUEST_URI'] ?? '-').(isset($_SERVER['HTTP_REFERER']) ? ' spa' : ''), (hrtime(true) - $perfTimingStart) / 1e6, $dbMs, count(get_included_files())),
        FILE_APPEND | LOCK_EX,
    );
});
