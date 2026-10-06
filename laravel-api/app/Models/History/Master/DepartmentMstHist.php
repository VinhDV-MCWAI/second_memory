<?php

namespace App\Models\History\Master;

use App\Models\Master\AdminMst;
use App\Models\Master\DepartmentMst;
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

    public function departmentMst()
    {
        return $this->belongsTo(DepartmentMst::class, 'department_mst_id');
    }

    public function author()
    {
        return $this->belongsTo(AdminMst::class, 'author_id');
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'department_mst_id' => 'integer',
            'code' => 'string',
            'name' => 'string',
            'status' => 'integer',
            'action' => 'integer',
            'author_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
