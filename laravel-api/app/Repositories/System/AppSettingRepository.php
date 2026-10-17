<?php

declare(strict_types=1);

namespace App\Repositories\System;

use App\Models\System\AppSetting;

/**
 * Owner-controlled switches (ADR-0020), one row per key.
 */
class AppSettingRepository
{
    public function get(string $key, mixed $default): mixed
    {
        $setting = AppSetting::query()->find($key);

        return $setting === null ? $default : $setting->value;
    }

    public function set(string $key, mixed $value, ?int $adminId): void
    {
        AppSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $adminId]);
    }
}
