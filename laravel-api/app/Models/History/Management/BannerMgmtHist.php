<?php

namespace App\Models\History\Management;

use Illuminate\Database\Eloquent\Model;

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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
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

    /**
     * Get the banner management record.
     */
    public function bannerMgmt()
    {
        return $this->belongsTo(\App\Models\Management\BannerMgmt::class, 'banner_mgmt_id');
    }

    /**
     * Get the author record.
     */
    public function author()
    {
        return $this->belongsTo(\App\Models\Master\AdminMst::class, 'author_id');
    }
}
