<?php

namespace App\Models\Master;

use App\Models\History\Master\AdminMstHist;
use App\Traits\HasHistory;
use App\Traits\HasSoftDelete;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminMst extends Model
{
    use HasFactory, HasHistory, HasSoftDelete, HasStatus;

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
        'avatar',
        'email_verified_at',
        'is_delete',
        'remember_token',
    ];

    /**
     * Get the roles associated with the admin.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            RoleMst::class,
            'admin_role_mst',
            'admin_mst_id',
            'role_mst_id'
        )->withTimestamps();
    }

    /**
     * Get the departments associated with the admin.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(
            DepartmentMst::class,
            'admin_department_mst',
            'admin_mst_id',
            'department_mst_id'
        )->withTimestamps();
    }

    /**
     * Get the history records for the admin.
     */
    public function history(): HasMany
    {
        return $this->hasMany(AdminMstHist::class, 'admin_mst_id');
    }

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
}
