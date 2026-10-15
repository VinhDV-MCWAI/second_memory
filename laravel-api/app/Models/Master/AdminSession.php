<?php

declare(strict_types=1);

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One device an admin is signed in on (AUTH-04). The session itself lives in Redis.
 *
 * @property int $id
 * @property int $admin_id
 * @property string $session_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 * @property Carbon $last_used_at
 */
class AdminSession extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'admin_session';

    protected $fillable = [
        'admin_id',
        'session_id',
        'ip_address',
        'user_agent',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'admin_id' => 'integer',
            'created_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }
}
