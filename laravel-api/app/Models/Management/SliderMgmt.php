<?php

declare(strict_types=1);

namespace App\Models\Management;

use App\Enums\StatusEnum;
use App\Models\History\Management\SliderMgmtHist;
use App\Traits\HasHistory;
use App\Traits\HasSoftDelete;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SliderMgmt extends Model
{
    use HasFactory, HasHistory, HasSoftDelete, HasStatus;

    protected $table = 'slider_mgmt';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'title',
        'slug',
        'link',
        'image',
        'status',
        'is_delete',
    ];

    /**
     * Get the history records for the slider.
     */
    public function history(): HasMany
    {
        return $this->hasMany(SliderMgmtHist::class, 'slider_mgmt_id');
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
            'link' => 'string',
            'image' => 'string',
            'status' => StatusEnum::class,
            'is_delete' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
