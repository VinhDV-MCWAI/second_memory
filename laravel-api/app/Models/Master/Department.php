<?php

declare(strict_types=1);

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A department (ADR-0017): admins belong to it, and later it is granted folders of the file manager.
 */
class Department extends Model
{
    use HasFactory;

    protected $table = 'department';

    protected $fillable = ['code', 'name', 'description', 'is_active', 'created_by', 'updated_by'];

    /**
     * @return BelongsToMany<AdminMst, $this>
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(AdminMst::class, 'admin_department', 'department_id', 'admin_id');
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'is_active' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
