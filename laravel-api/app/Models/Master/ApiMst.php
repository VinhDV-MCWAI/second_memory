<?php

declare(strict_types=1);

namespace App\Models\Master;

use App\Models\History\Master\ApiMstHist;
use App\Traits\HasHistory;
use App\Traits\HasSoftDelete;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiMst extends Model
{
    use HasFactory, HasHistory, HasSoftDelete, HasStatus;

    protected $table = 'api_mst';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'type',
        'name',
        'path',
        'is_active',
        'feature_mst_id',
        'is_delete',
    ];

    /**
     * Get the feature that owns the API.
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(FeatureMst::class, 'feature_mst_id');
    }

    /**
     * Get the roles associated with the API.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            RoleMst::class,
            'api_role_mst',
            'api_mst_id',
            'role_mst_id'
        )->withTimestamps();
    }

    /**
     * Get the history records for the API.
     */
    public function history(): HasMany
    {
        return $this->hasMany(ApiMstHist::class, 'api_mst_id');
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'type' => 'integer',
            'name' => 'string',
            'path' => 'string',
            'is_active' => 'boolean',
            'feature_mst_id' => 'integer',
            'is_delete' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
