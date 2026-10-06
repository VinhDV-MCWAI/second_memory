<?php

namespace App\Models\History\Master;

use Illuminate\Database\Eloquent\Model;

class DepartmentMstHist extends Model
{
    protected $table = 'department_mst_hist';

    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'department_mst_id',
        'code',
        'name',
        'status',
        'action',
        'author_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'department_mst_id' => 'integer',
        'code' => 'string',
        'name' => 'string',
        'status' => 'integer',
        'action' => 'integer',
        'author_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function departmentMst()
    {
        return $this->belongsTo(\App\Models\Master\DepartmentMst::class, 'department_mst_id');
    }

    public function author()
    {
        return $this->belongsTo(\App\Models\Master\AdminMst::class, 'author_id');
    }
}
