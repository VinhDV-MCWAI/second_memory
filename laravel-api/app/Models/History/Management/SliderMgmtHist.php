<?php

namespace App\Models\History\Management;

use Illuminate\Database\Eloquent\Model;

class SliderMgmtHist extends Model
{
  protected $table = 'slider_mgmt_hist';
  const UPDATED_AT = null;

  /**
   * The attributes that are mass assignable.
   *
   * @var string[]
   */
  protected $fillable = [
    'slider_mgmt_id',
    'title',
    'slug',
    'link',
    'image',
    'status',
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
    'slider_mgmt_id' => 'integer',
    'title' => 'string',
    'slug' => 'string',
    'link' => 'string',
    'image' => 'string',
    'status' => 'integer',
    'action' => 'integer',
    'author_id' => 'integer',
    'created_at' => 'datetime',
  ];

  public function sliderMgmt()
  {
    return $this->belongsTo(\App\Models\Management\SliderMgmt::class, 'slider_mgmt_id');
  }

  public function author()
  {
    return $this->belongsTo(\App\Models\Master\AdminMst::class, 'author_id');
  }
}
