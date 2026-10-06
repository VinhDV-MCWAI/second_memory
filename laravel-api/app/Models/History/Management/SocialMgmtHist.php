<?php

declare(strict_types=1);

namespace App\Models\History\Management;

use App\Models\Management\SocialMgmt;
use App\Models\Master\AdminMst;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialMgmtHist extends Model
{
    protected $table = 'social_mgmt_hist';

    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'social_mgmt_id',
        'name',
        'slug',
        'link',
        'image',
        'status',
        'is_display',
        'rank_order',
        'action',
        'author_id',
        'created_at',
    ];

    public function socialMgmt(): BelongsTo
    {
        return $this->belongsTo(SocialMgmt::class, 'social_mgmt_id');
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
            'social_mgmt_id' => 'integer',
            'name' => 'string',
            'slug' => 'string',
            'link' => 'string',
            'image' => 'string',
            'status' => 'integer',
            'is_display' => 'boolean',
            'rank_order' => 'integer',
            'action' => 'integer',
            'author_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
