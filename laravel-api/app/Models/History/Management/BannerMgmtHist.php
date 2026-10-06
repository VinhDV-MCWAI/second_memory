<?php

declare(strict_types=1);

namespace App\Models\History\Management;

use App\Models\Management\BannerMgmt;
use App\Models\Master\AdminMst;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannerMgmtHist extends Model
{
    protected $table = 'banner_mgmt_hist';

    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'banner_mgmt_id',
        'title',
        'slug',
        'description',
        'link',
        'image',
        'position',
        'status',
        'action',
        'author_id',
        'created_at',
    ];

    /**
     * Get the banner management record.
     */
    public function bannerMgmt(): BelongsTo
    {
        return $this->belongsTo(BannerMgmt::class, 'banner_mgmt_id');
    }

    /**
     * Get the author record.
     */
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
            'banner_mgmt_id' => 'integer',
            'title' => 'string',
            'slug' => 'string',
            'description' => 'string',
            'link' => 'string',
            'image' => 'string',
            'position' => 'string',
            'status' => 'integer',
            'action' => 'integer',
            'author_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
