<?php

declare(strict_types=1);

namespace App\Models\Master;

use App\Enums\DepartmentStatus;
use App\Models\History\Master\DepartmentMstHist;
use App\Traits\HasHistory;
use App\Traits\HasSoftDelete;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentMst extends Model
{
    use HasFactory, HasHistory, HasSoftDelete, HasStatus;

    protected $table = 'department_mst';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'code',
        'name',
        'status',
        'is_delete',
    ];

    /**
     * Get the admins associated with the department.
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(
            AdminMst::class,
            'admin_department_mst',
            'department_mst_id',
            'admin_mst_id'
        )->withTimestamps();
    }

    /**
     * Get the policies associated with the department.
     */
    public function policies(): BelongsToMany
    {
        return $this->belongsToMany(
            PolicyDepartmentMst::class,
            'department_management_mst',
            'department_mst_id',
            'policy_department_mst_id'
        )->withTimestamps();
    }

    /**
     * Get the history records for the department.
     */
    public function history(): HasMany
    {
        return $this->hasMany(DepartmentMstHist::class, 'department_mst_id');
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'code' => 'string',
            'name' => 'string',
            'status' => DepartmentStatus::class,
            'is_delete' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
