<?php

declare(strict_types=1);

namespace App\Models\History\Master;

use App\Models\Master\AdminMst;
use App\Models\Master\RoleMst;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleMstHist extends Model
{
    protected $table = 'role_mst_hist';

    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'role_mst_id',
        'name',
        'permission',
        'is_active',
        'action',
        'author_id',
        'created_at',
    ];

    public function roleMst(): BelongsTo
    {
        return $this->belongsTo(RoleMst::class, 'role_mst_id');
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
            'role_mst_id' => 'integer',
            'name' => 'string',
            'permission' => 'string',
            'is_active' => 'boolean',
            'action' => 'integer',
            'author_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
