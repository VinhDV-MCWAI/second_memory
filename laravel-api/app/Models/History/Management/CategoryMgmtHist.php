<?php

namespace App\Models\History\Management;

use Illuminate\Database\Eloquent\Model;

class CategoryMgmtHist extends Model
{
    protected $table = 'category_mgmt_hist';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'category_mgmt_id',
        'name',
        'slug',
        'description',
        'status',
        'is_display',
        'rank_order',
        'layout_structure',
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
        'category_mgmt_id' => 'integer',
        'name' => 'string',
        'slug' => 'string',
        'description' => 'string',
        'status' => 'integer',
        'is_display' => 'boolean',
        'rank_order' => 'integer',
        'layout_structure' => 'array',
        'action' => 'integer',
        'author_id' => 'integer',
        'created_at' => 'datetime',
    ];

    const UPDATED_AT = null;

    /**
     * Get the category management record.
     */
    public function categoryMgmt()
    {
        return $this->belongsTo(\App\Models\Management\CategoryMgmt::class, 'category_mgmt_id');
    }

    /**
     * Get the author record.
     */
    public function author()
    {
        return $this->belongsTo(\App\Models\Master\AdminMst::class, 'author_id');
    }
}
