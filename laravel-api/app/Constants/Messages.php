<?php

declare(strict_types=1);

namespace App\Constants;

class Messages
{
    /**
     * Notification message
     */
    const N0001 = 'temp';

    /**
     * Warning message
     */
    const W0001 = 'temp';

    /**
     * Errors validation messages
     */
    const E0001 = '{attributes} invalid type numeric';      // Numeric

    const E0002 = '{attributes} invalid type string';       // String

    const E0003 = '{attributes} invalid type float';        // Float

    const E0004 = '{attributes} invalid type double';       // Double

    const E0005 = '{attributes} invalid type boolean';      // Boolean

    const E0006 = '{attributes} invalid type email';        // email

    const E0007 = '{attributes} is required';               // Required

    const E0008 = 'Unique {attributes}';                    // Unique

    const E0009 = '{attributes} invalid date format';       // Date

    const E0010 = '{attributes} min is {number}';           // Min

    const E0011 = '{attributes} max is {number}';           // Max

    const E0012 = '{attributes} invalid date';              // Date

    const E0013 = '{attributes} invalid date format';       // Date format

    const E0014 = '{attributes} bigger than {attributes2}'; // Bigger date

    const E0015 = '{attributes} not in the range {range}';  // Not in the range

    const E0016 = 'Cannot delete or update this record '    // Fk constraint violation
        .'because it is referenced in another table.';

    const E0017 = '{attributes} not exist in {tableName}';  // Not exit in table

    const E0018 = 'Do not allow editing of personal role';  // Don't edit my role

    const E0019 = '{attributes} invalid type array';        // Array

    const E0020 = '{attributes} exist in {tableName}';      // Exit in table

    const E0021 = 'At least one active owner must remain';

    const E0025 = 'A built-in role cannot be renamed or deleted';

    const E0026 = 'The owner role always holds every permission';

    const E0027 = 'This role is still assigned to an admin';

    const E0028 = 'This folder does not exist';

    const E0029 = 'An item with this name already exists in the folder';

    const E0030 = 'The file has not been uploaded yet';

    const E0031 = 'The uploaded file does not match the declared size';

    const E0032 = 'This upload is already finished';

    const E0033 = 'A folder cannot be moved into itself';

    const E0034 = 'The file is not ready yet';

    const E0035 = 'This file type is not allowed';

    const E0036 = 'The background task failed';

    const E0037 = 'Some parts of the file are missing';

    const E0038 = 'The uploaded parts could not be joined into one file';

    const E0039 = 'This file type cannot be previewed';

    const E0040 = 'This file is no longer kept and cannot be restored';

    const E0041 = 'Only uploaded files of the file manager can be attached';

    const E0042 = 'Give a link or attach at least one file';

    const E0043 = 'Log out to end the session you are using';

    const E0044 = 'This folder holds folders you cannot use';

    const E0045 = 'Only folders can be given to departments';

    const E0046 = 'Registration is closed';

    const E0047 = 'Move or delete the categories inside this one first';

    const E0048 = 'A category cannot go under itself or a category inside it';

    const E0049 = 'An article cannot go under itself or one of its sub-pages';

    const E0050 = 'Sub-pages can go at most 10 levels deep';

    const E0051 = 'Move or delete the sub-pages of this article first';

    const E0052 = 'The parent is not one of the published notes';

    const E0053 = 'Notes cannot be parents of each other in a loop';

    const E0054 = 'No mail server is known for this email domain: check it for a typo';

    const E0055 = 'Categories can go at most 5 levels deep';

    /**
     * Errors http messages
     */
    const E0401 = 'Unauthorized Access';

    const E0403 = 'Access is forbidden';

    const E0404 = 'Not Found';

    const E0405 = 'Method Not Allowed';

    const E0422 = 'Invalid argument';

    const E0429 = 'Too many requests';

    const E0500 = 'Internal server error';

    const TEST = 'demo test message2';

    /**
     * Get message
     *
     * @param  array  $attributes  = []
     */
    public static function getMessage(string $message, array $attributes = []): string
    {
        if (! empty($attributes)) {
            foreach ($attributes as $key => $value) {
                $attr = '{'.$key.'}';
                $message = str_replace($attr, $value, $message);
            }
        }

        return $message;
    }
}
