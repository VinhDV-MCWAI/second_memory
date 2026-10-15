<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown from BackgroundJob::run() when trying again cannot help (bad input, missing data): the job
 * fails at once, and the message (a Messages constant, safe to show) reaches the screen.
 */
final class BackgroundTaskFailed extends RuntimeException {}
