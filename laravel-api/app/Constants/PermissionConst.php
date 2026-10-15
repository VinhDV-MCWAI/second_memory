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

    /** Separates the feature from the action in a permission name: `skill.list` → feature `skill`. */
    public const SEPARATOR = '.';

    public const ROLE_NAME_MAX = 50;

    public const PER_PAGE = 15;

    /** Admin routes every signed-in admin may call, so they are not permissions. */
    public const ALWAYS_ALLOWED = [
        'credential.me',
        // Sessions screen (AUTH-04): only the admin's own sessions
        'admin-session.list',
        'admin-session.delete',
        'admin-session.delete-others',
    ];
}
