<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when Google Drive credentials are invalid or expired
 */
class GoogleDriveAuthException extends Exception
{
    protected $code = 401;

    public function __construct(string $message = 'Google Drive authentication failed')
    {
        parent::__construct($message, $this->code);
    }
}
