<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentManagementMst extends Model
{
    protected $table = 'department_management_mst';

    public $incrementing = false;

    protected $primaryKey = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'department_mst_id',
        'policy_department_mst_id',
    ];

    /**
     * Get the department that owns this relationship.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(DepartmentMst::class, 'department_mst_id');
    }

    /**
     * Get the policy that owns this relationship.
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(PolicyDepartmentMst::class, 'policy_department_mst_id');
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'department_mst_id' => 'integer',
            'policy_department_mst_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
