<?php

namespace App\Models\History\Management;

use Illuminate\Database\Eloquent\Model;

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

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'setting_link_mgmt_id' => 'integer',
        'key' => 'string',
        'value' => 'string',
        'action' => 'integer',
        'author_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function settingLinkMgmt()
    {
        return $this->belongsTo(\App\Models\Management\SettingLinkMgmt::class, 'setting_link_mgmt_id');
    }

    public function author()
    {
        return $this->belongsTo(\App\Models\Master\AdminMst::class, 'author_id');
    }
}
