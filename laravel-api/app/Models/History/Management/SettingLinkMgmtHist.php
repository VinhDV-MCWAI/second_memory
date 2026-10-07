<?php

declare(strict_types=1);

namespace App\Models\History\Management;

use App\Models\Management\SettingLinkMgmt;
use App\Models\Master\AdminMst;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingLinkMgmtHist extends Model
{
    protected $table = 'setting_link_mgmt_hist';

    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'setting_link_mgmt_id',
        'key',
        'value',
        'action',
        'author_id',
        'created_at',
    ];

    public function settingLinkMgmt(): BelongsTo
    {
        return $this->belongsTo(SettingLinkMgmt::class, 'setting_link_mgmt_id');
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
            'setting_link_mgmt_id' => 'integer',
            'key' => 'string',
            'value' => 'string',
            'action' => 'integer',
            'author_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
