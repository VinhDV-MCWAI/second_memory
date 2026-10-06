<?php

declare(strict_types=1);

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminRoleMst extends Model
{
    protected $table = 'admin_role_mst';

    public $incrementing = false;

    protected $primaryKey = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'admin_mst_id',
        'role_mst_id',
    ];

    /**
     * Get the admin that owns this relationship.
     */
    public function adminMst(): BelongsTo
    {
        return $this->belongsTo(AdminMst::class, 'admin_mst_id');
    }

    /**
     * Get the role that owns this relationship.
     */
    public function roleMst(): BelongsTo
    {
        return $this->belongsTo(RoleMst::class, 'role_mst_id');
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'admin_mst_id' => 'integer',
            'role_mst_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
