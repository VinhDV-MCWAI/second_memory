<?php

declare(strict_types=1);

namespace App\Models\Master;

use App\Models\History\Master\PolicyDepartmentMstHist;
use App\Traits\HasHistory;
use App\Traits\HasSoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PolicyDepartmentMst extends Model
{
    use HasFactory, HasHistory, HasSoftDelete;

    protected $table = 'policy_department_mst';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'table_name',
        'row_id',
        'is_delete',
    ];

    /**
     * Get the departments associated with the policy.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(
            DepartmentMst::class,
            'department_management_mst',
            'policy_department_mst_id',
            'department_mst_id'
        )->withTimestamps();
    }

    /**
     * Get the history records for the policy.
     */
    public function history(): HasMany
    {
        return $this->hasMany(PolicyDepartmentMstHist::class, 'policy_department_mst_id');
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'table_name' => 'string',
            'row_id' => 'integer',
            'is_delete' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
