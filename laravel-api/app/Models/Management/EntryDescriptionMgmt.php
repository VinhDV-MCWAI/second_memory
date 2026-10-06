<?php

namespace App\Models\Management;

use App\Models\History\Management\EntryDescriptionMgmtHist;
use App\Traits\HasHistory;
use App\Traits\HasSoftDelete;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntryDescriptionMgmt extends Model
{
    use HasFactory, HasHistory, HasSoftDelete, HasStatus;

    protected $table = 'entry_description_mgmt';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'title',
        'summary',
        'article',
        'status',
        'is_display',
        'rank_order',
        'is_delete',
    ];

    /**
     * Get the history records for the entry description.
     */
    public function history(): HasMany
    {
        return $this->hasMany(EntryDescriptionMgmtHist::class, 'entry_description_mgmt_id');
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'title' => 'string',
            'summary' => 'string',
            'article' => 'array',
            'status' => 'integer',
            'is_display' => 'boolean',
            'rank_order' => 'integer',
            'is_delete' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
