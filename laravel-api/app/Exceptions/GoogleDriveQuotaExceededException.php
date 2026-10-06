<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when Google Drive quota is exceeded
 */
class GoogleDriveQuotaExceededException extends Exception
{
    protected $code = 507; // Insufficient Storage

    public function __construct(string $message = 'Google Drive storage quota exceeded')
    {
        parent::__construct($message, $this->code);
    }
}
