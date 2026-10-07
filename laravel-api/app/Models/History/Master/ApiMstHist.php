<?php

declare(strict_types=1);

namespace App\Models\History\Master;

use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiMstHist extends Model
{
    protected $table = 'api_mst_hist';

    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'api_mst_id',
        'type',
        'name',
        'path',
        'is_active',
        'feature_mst_id',
        'action',
        'author_id',
        'created_at',
    ];

    public function apiMst(): BelongsTo
    {
        return $this->belongsTo(ApiMst::class);
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
            'api_mst_id' => 'integer',
            'type' => 'integer',
            'name' => 'string',
            'path' => 'string',
            'is_active' => 'integer',
            'feature_mst_id' => 'integer',
            'action' => 'integer',
            'author_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
