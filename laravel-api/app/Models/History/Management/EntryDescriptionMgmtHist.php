<?php

declare(strict_types=1);

namespace App\Models\History\Management;

use App\Models\Management\EntryDescriptionMgmt;
use App\Models\Master\AdminMst;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryDescriptionMgmtHist extends Model
{
    protected $table = 'entry_description_mgmt_hist';

    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'entry_description_mgmt_id',
        'title',
        'summary',
        'article',
        'status',
        'is_display',
        'rank_order',
        'action',
        'author_id',
        'created_at',
    ];

    public function entryDescriptionMgmt(): BelongsTo
    {
        return $this->belongsTo(EntryDescriptionMgmt::class, 'entry_description_mgmt_id');
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
            'entry_description_mgmt_id' => 'integer',
            'title' => 'string',
            'summary' => 'string',
            'article' => 'string',
            'status' => 'integer',
            'is_display' => 'boolean',
            'rank_order' => 'integer',
            'action' => 'integer',
            'author_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
