<?php

declare(strict_types=1);

namespace App\Models\Management;

use App\Enums\StatusEnum;
use App\Models\History\Management\BannerMgmtHist;
use App\Traits\HasHistory;
use App\Traits\HasSoftDelete;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BannerMgmt extends Model
{
    use HasFactory, HasHistory, HasSoftDelete, HasStatus;

    protected $table = 'banner_mgmt';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'position',
        'status',
        'is_delete',
        'media_id',
        'status',
        'is_delete',
        'media_id',
    ];

    /**
     * Get the history records for the banner.
     */
    public function history(): HasMany
    {
        return $this->hasMany(BannerMgmtHist::class, 'banner_mgmt_id');
    }

    /**
     * Get the media record.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaMgmt::class, 'media_id');
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'title' => 'string',
            'slug' => 'string',
            'description' => 'string',
            'position' => 'string',
            'position' => 'string',
            'status' => StatusEnum::class,
            'is_delete' => 'boolean',
            'media_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
