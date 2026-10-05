<?php

namespace App\Models\Management;

use App\Models\History\Management\SocialMgmtHist;
use App\Traits\HasSoftDelete;
use App\Traits\HasStatus;
use App\Traits\HasHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialMgmt extends Model
{
    use HasSoftDelete, HasStatus, HasHistory, HasFactory;

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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'slug' => 'string',
        'link' => 'string',
        'image' => 'string',
        'status' => 'integer',
        'is_display' => 'boolean',
        'rank_order' => 'integer',
        'is_delete' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the history records for the social.
     *
     * @return HasMany
     */
    public function history(): HasMany
    {
        return $this->hasMany(SocialMgmtHist::class, 'social_mgmt_id');
    }
}
