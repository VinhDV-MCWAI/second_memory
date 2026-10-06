<?php

declare(strict_types=1);

namespace App\Models\History\Management;

use App\Models\Management\CategoryMgmt;
use App\Models\Master\AdminMst;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'created_at',
    ];

    const UPDATED_AT = null;

    /**
     * Get the category management record.
     */
    public function categoryMgmt(): BelongsTo
    {
        return $this->belongsTo(CategoryMgmt::class, 'category_mgmt_id');
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
    }
}
