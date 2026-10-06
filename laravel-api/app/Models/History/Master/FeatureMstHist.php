<?php

declare(strict_types=1);

namespace App\Models\History\Master;

use App\Models\Master\AdminMst;
use App\Models\Master\FeatureMst;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureMstHist extends Model
{
    protected $table = 'feature_mst_hist';

    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'feature_mst_id',
        'name',
        'group_name',
        'description',
        'status',
        'action',
        'author_id',
        'created_at',
    ];

    public function featureMst(): BelongsTo
    {
        return $this->belongsTo(FeatureMst::class, 'feature_mst_id');
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
            'feature_mst_id' => 'integer',
            'name' => 'string',
            'group_name' => 'string',
            'description' => 'string',
            'status' => 'integer',
            'action' => 'integer',
            'author_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
