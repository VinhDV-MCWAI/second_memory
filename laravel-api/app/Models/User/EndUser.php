<?php

declare(strict_types=1);

namespace App\Models\User;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A visitor's account on the public site (ADR-0020). Separate from admins: own table, own guard.
 */
class EndUser extends Model implements AuthenticatableContract
{
    use Authenticatable, HasFactory;

    protected $table = 'end_user';

    protected $fillable = ['email', 'display_name', 'password', 'is_active', 'last_login_at'];

    protected $hidden = ['password'];

    /** No remember_token column: "remember me" is not offered to end users */
    public function getRememberTokenName(): string
    {
        return '';
    }

    /**
     * @return HasMany<EndUserSaved, $this>
     */
    public function savedItems(): HasMany
    {
        return $this->hasMany(EndUserSaved::class);
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
