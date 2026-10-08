<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What an audit_log row records (ADR-0006).
 */
enum AuditEvent: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
    case LOGGED_IN = 'logged_in';
    case LOGGED_OUT = 'logged_out';
    case LOGIN_FAILED = 'login_failed';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'created',
            self::UPDATED => 'updated',
            self::DELETED => 'deleted',
            self::LOGGED_IN => 'logged in',
            self::LOGGED_OUT => 'logged out',
            self::LOGIN_FAILED => 'login failed',
        };
    }
}
