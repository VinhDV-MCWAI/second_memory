<?php

declare(strict_types=1);

namespace App\Models\History\Master;

use App\Models\Master\AdminMst;
use App\Models\Master\PolicyDepartmentMst;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyDepartmentMstHist extends Model
{
    protected $table = 'policy_department_mst_hist';

    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'policy_department_mst_id',
        'table_name',
        'row_id',
        'action',
        'author_id',
        'created_at',
    ];

    public function policyDepartmentMst(): BelongsTo
    {
        return $this->belongsTo(PolicyDepartmentMst::class, 'policy_department_mst_id');
    }

    public function author(): BelongsTo
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
            'policy_department_mst_id' => 'integer',
            'table_name' => 'string',
            'row_id' => 'integer',
            'action' => 'integer',
            'author_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
