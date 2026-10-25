<?php

declare(strict_types=1);

namespace App\Rules;

use App\Constants\Messages;
use Closure;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The domain of an email has a mail record (the check `email:dns` makes), with its own message:
 * `email:rfc,dns` answers "not a valid email address" for a typo in the domain too.
 */
final class EmailDomainExists implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! (new EmailValidator)->isValid((string) $value, new DNSCheckValidation)) {
            $fail(Messages::E0054);
        }
    }
}
