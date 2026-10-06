<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when a file is not found on Google Drive
 * This indicates the file exists in database but not on Google Drive
 */
class GoogleDriveFileNotFoundException extends Exception
{
    protected $code = 404;

    public function __construct(string $fileId, string $fileName = '')
    {
        $message = $fileName
            ? "File '{$fileName}' (ID: {$fileId}) not found on Google Drive"
            : "File (ID: {$fileId}) not found on Google Drive";

        parent::__construct($message, $this->code);
    }
}
