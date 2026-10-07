<?php

declare(strict_types=1);

namespace App\Models\Management;

use App\Enums\StatusEnum;
use App\Models\History\Management\SocialMgmtHist;
use App\Traits\HasHistory;
use App\Traits\HasSoftDelete;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property StatusEnum|null $status
 */
class SocialMgmt extends Model
{
    use HasFactory, HasHistory, HasSoftDelete, HasStatus;

    protected $table = 'social_mgmt';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'slug',
        'link',
        'image',
        'status',
        'is_display',
        'rank_order',
        'is_delete',
    ];

    /**
     * Get the history records for the social.
     */
    public function history(): HasMany
    {
        return $this->hasMany(SocialMgmtHist::class, 'social_mgmt_id');
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
            'link' => 'string',
            'image' => 'string',
            'status' => StatusEnum::class,
            'is_display' => 'boolean',
            'rank_order' => 'integer',
            'is_delete' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
