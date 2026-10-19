<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * End-user accounts of the public site (ADR-0020).
 */
final class EndUserConst
{
    /** Guard of end users; admins use CommonVal::ADMIN_GUARD */
    public const GUARD = 'user';

    public const USERNAME_MIN = 3;

    public const USERNAME_MAX = 30;

    /** Letters (any case, stored lower case), digits, dot, underscore, dash */
    public const USERNAME_PATTERN = '/^[A-Za-z0-9._-]+$/';

    public const DISPLAY_NAME_MAX = 50;

    public const PASSWORD_MIN = 8;

    public const PASSWORD_MAX = 100;

    public const PER_PAGE = 15;

    /** Every /api/user route, per IP */
    public const RATE_PER_MINUTE = 60;

    /** New accounts per IP and hour */
    public const REGISTER_PER_HOUR = 5;

    /** app_setting key of the registration switch (on when the row is missing) */
    public const SETTING_REGISTRATION_OPEN = 'end_user_registration_open';

    /** audit_log.auditable_type */
    public const AUDITABLE_TYPE = 'end_user';

    public const SETTING_AUDITABLE_TYPE = 'app_setting';
}
