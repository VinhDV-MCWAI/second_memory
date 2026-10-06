<?php

namespace App\Models\History\Master;

use Illuminate\Database\Eloquent\Model;

class FeatureMstHist extends Model
{
    protected $table = 'feature_mst_hist';

    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'feature_mst_id',
        'name',
        'group_name',
        'description',
        'status',
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
        'feature_mst_id' => 'integer',
        'name' => 'string',
        'group_name' => 'string',
        'description' => 'string',
        'status' => 'integer',
        'action' => 'integer',
        'author_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function featureMst()
    {
        return $this->belongsTo(\App\Models\Master\FeatureMst::class, 'feature_mst_id');
    }

    public function author()
    {
        return $this->belongsTo(\App\Models\Master\AdminMst::class, 'author_id');
    }
}
