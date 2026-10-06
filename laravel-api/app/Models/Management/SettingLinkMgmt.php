<?php

namespace App\Models\Management;

use App\Models\History\Management\SettingLinkMgmtHist;
use App\Traits\HasHistory;
use App\Traits\HasSoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SettingLinkMgmt extends Model
{
    use HasFactory, HasHistory, HasSoftDelete;

    protected $table = 'setting_link_mgmt';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'key',
        'value',
        'is_delete',
    ];

    /**
     * Get the history records for the setting link.
     */
    public function history(): HasMany
    {
        return $this->hasMany(SettingLinkMgmtHist::class, 'setting_link_mgmt_id');
    }

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'key' => 'string',
            'value' => 'string',
            'is_delete' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
