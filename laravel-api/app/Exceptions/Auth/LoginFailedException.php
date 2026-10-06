<?php

declare(strict_types=1);

namespace App\Exceptions\Auth;

use Illuminate\Auth\Access\AuthorizationException;

class LoginFailedException extends AuthorizationException
{
    //
}
