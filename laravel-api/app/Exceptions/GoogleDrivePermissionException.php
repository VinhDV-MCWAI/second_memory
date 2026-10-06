<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when there's a permission issue with Google Drive
 */
class GoogleDrivePermissionException extends Exception
{
    protected $code = 403;

    public function __construct(string $fileId = '', string $operation = '')
    {
        $message = $operation && $fileId
            ? "Permission denied to {$operation} file (ID: {$fileId}) on Google Drive"
            : 'Permission denied on Google Drive';

        parent::__construct($message, $this->code);
    }
}
