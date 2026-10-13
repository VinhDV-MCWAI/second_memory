<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Dynamic permissions (ADR-0013): one permission per named admin route.
 */
final class PermissionConst
{
    /** Routes behind this middleware alias (with or without abilities) are checked against permissions. */
    public const ADMIN_MIDDLEWARE = 'auth.admin';

    /** Admin routes every signed-in admin may call, so they are not permissions. */
    public const ALWAYS_ALLOWED = ['credential.me'];
}
