<?php

declare(strict_types=1);

namespace App\Models\Master;

use App\Constants\CommonVal;
use App\Enums\AdminRole;
use App\Traits\HasSoftDelete;
use App\Traits\HasStatus;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Laravel\Sanctum\Contracts\HasAbilities;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;

class AdminMst extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory, HasSoftDelete, HasStatus;

    /**
     * API tokens only for the importer CLI (ADR-0010); a session login (ADR-0004) carries a TransientToken.
     *
     * @use HasApiTokens<HasAbilities>
     */
    use HasApiTokens;

    /** Dynamic permissions (ADR-0013): roles and permissions live in the spatie tables. */
    use HasRoles;

    protected $table = 'admin_mst';

    protected string $guard_name = CommonVal::ADMIN_GUARD;

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
            'avatar' => 'string',
            'email_verified_at' => 'datetime',
            'is_delete' => 'boolean',
            'remember_token' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Departments the admin belongs to (ADR-0017); several are allowed.
     *
     * @return BelongsToMany<Department, $this>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'admin_department', 'admin_id', 'department_id');
    }

    /**
     * Names of the permissions this admin holds; the owner role holds them all (ADR-0013).
     *
     * @return list<string>
     */
    public function permissionNames(): array
    {
        $permissions = $this->hasRole(AdminRole::OWNER->value)
            ? Permission::query()->where('guard_name', CommonVal::ADMIN_GUARD)->get()
            : $this->getAllPermissions();

        return $permissions->pluck('name')->sort()->values()->all();
    }
}
