<?php

declare(strict_types=1);

// Reads a PERF_TIMING_LOG (see timing-prepend.php) on stdin and prints, per URI, the median PHP
// time, database time and loaded files. The first $argv[1] requests per URI are warm-up and skipped.

$warmup = (int) ($argv[1] ?? 20);
$byUri = [];
while (($line = fgets(STDIN)) !== false) {
    [$uri, $php, $db, $files] = explode("\t", trim($line));
    $byUri[$uri][] = [(float) $php, (float) $db, (int) $files];
}

$median = static function (array $values): float {
    sort($values);
    $n = count($values);

    return $n % 2 ? $values[intdiv($n, 2)] : ($values[$n / 2 - 1] + $values[$n / 2]) / 2;
};

printf("%-34s %5s %8s %8s %6s\n", 'uri', 'n', 'php ms', 'db ms', 'files');
foreach ($byUri as $uri => $rows) {
    $rows = array_slice($rows, $warmup);
    if ($rows === []) {
        continue;
    }
    printf(
        "%-34s %5d %8.1f %8.1f %6d\n",
        $uri,
        count($rows),
        $median(array_column($rows, 0)),
        $median(array_column($rows, 1)),
        (int) $median(array_column($rows, 2)),
    );
}
