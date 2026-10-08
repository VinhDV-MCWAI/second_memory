<?php

declare(strict_types=1);

namespace App\Models\Master;

use App\Enums\AdminRole;
use App\Traits\HasSoftDelete;
use App\Traits\HasStatus;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\Contracts\HasAbilities;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property AdminRole $role
 */
class AdminMst extends Model implements AuthenticatableContract
{
    use Authenticatable, HasFactory, HasSoftDelete, HasStatus;

    /**
     * API tokens only for the importer CLI (ADR-0010); a session login (ADR-0004) carries a TransientToken.
     *
     * @use HasApiTokens<HasAbilities>
     */
    use HasApiTokens;

    protected $table = 'admin_mst';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'email',
        'user_name',
        'password',
        'first_name',
        'last_name',
        'address',
        'phone_number',
        'birth',
        'gender',
        'status',
        'is_active',
        'role',
        'avatar',
        'email_verified_at',
        'is_delete',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'email' => 'string',
            'user_name' => 'string',
            'password' => 'string',
            'first_name' => 'string',
            'last_name' => 'string',
            'address' => 'string',
            'phone_number' => 'string',
            'birth' => 'datetime',
            'gender' => 'integer',
            'status' => 'integer',
            'is_active' => 'boolean',
            'role' => AdminRole::class,
            'avatar' => 'string',
            'email_verified_at' => 'datetime',
            'is_delete' => 'boolean',
            'remember_token' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
