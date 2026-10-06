<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminDepartmentMst extends Model
{
    protected $table = 'admin_department_mst';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'admin_mst_id',
        'department_mst_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'admin_mst_id' => 'integer',
        'department_mst_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admin that owns this relationship.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminMst::class, 'admin_mst_id');
    }

    /**
     * Get the department that owns this relationship.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(DepartmentMst::class, 'department_mst_id');
    }
}
