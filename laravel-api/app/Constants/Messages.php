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
