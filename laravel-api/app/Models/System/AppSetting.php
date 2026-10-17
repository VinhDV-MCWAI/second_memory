<?php

declare(strict_types=1);

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

/**
 * A switch the owner changes in the admin screens, e.g. whether visitors may register (ADR-0020).
 */
class AppSetting extends Model
{
    public const CREATED_AT = null;

    public $incrementing = false;

    protected $table = 'app_setting';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'updated_by'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
            'updated_by' => 'integer',
            'updated_at' => 'datetime',
        ];
    }
}
