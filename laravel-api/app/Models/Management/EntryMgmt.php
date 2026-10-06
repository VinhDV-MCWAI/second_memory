<?php

namespace App\Models\Management;

use App\Enums\StatusEnum;
use App\Models\History\Management\EntryMgmtHist;
use App\Traits\HasHistory;
use App\Traits\HasSoftDelete;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntryMgmt extends Model
{
    use HasFactory, HasHistory, HasSoftDelete, HasStatus;

    protected $table = 'entry_mgmt';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'slug',
        'status',
        'is_display',
        'rank_order',
        'is_delete',
        'layout_structure',
    ];

    /**
     * Get the history records for the entry.
     */
    public function history(): HasMany
    {
        return $this->hasMany(EntryMgmtHist::class, 'entry_mgmt_id');
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'name' => 'string',
            'slug' => 'string',
            'status' => StatusEnum::class,
            'is_display' => 'boolean',
            'rank_order' => 'integer',
            'is_delete' => 'boolean',
            'layout_structure' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
