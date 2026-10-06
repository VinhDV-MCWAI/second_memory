<?php

namespace App\Models\History\Master;

use Illuminate\Database\Eloquent\Model;

class ApiMstHist extends Model
{
    protected $table = 'api_mst_hist';

    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'api_mst_id',
        'type',
        'name',
        'path',
        'is_active',
        'feature_mst_id',
        'action',
        'author_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'api_mst_id' => 'integer',
        'type' => 'integer',
        'name' => 'string',
        'path' => 'string',
        'is_active' => 'integer',
        'feature_mst_id' => 'integer',
        'action' => 'integer',
        'author_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function apiMst()
    {
        return $this->belongsTo(\App\Models\Master\ApiMst::class);
    }

    public function author()
    {
        return $this->belongsTo(\App\Models\Master\AdminMst::class, 'author_id');
    }
}
