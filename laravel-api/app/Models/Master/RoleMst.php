<?php

declare(strict_types=1);

namespace App\Models\Master;

use App\Models\History\Master\RoleMstHist;
use App\Traits\HasHistory;
use App\Traits\HasSoftDelete;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoleMst extends Model
{
    use HasFactory, HasHistory, HasSoftDelete, HasStatus;

    protected $table = 'role_mst';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'permission',
        'is_active',
        'is_delete',
    ];

    /**
     * Get the admins associated with the role.
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(
            AdminMst::class,
            'admin_role_mst',
            'role_mst_id',
            'admin_mst_id'
        )->withTimestamps();
    }

    /**
     * Get the APIs associated with the role.
     */
    public function apis(): BelongsToMany
    {
        return $this->belongsToMany(
            ApiMst::class,
            'api_role_mst',
            'role_mst_id',
            'api_mst_id'
        )->withTimestamps();
    }

    /**
     * Get the history records for the role.
     */
    public function history(): HasMany
    {
        return $this->hasMany(RoleMstHist::class, 'role_mst_id');
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'name' => 'string',
            'permission' => 'string',
            'is_active' => 'boolean',
            'is_delete' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
