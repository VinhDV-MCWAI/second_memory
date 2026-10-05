<?php

namespace App\Models\History\Management;

use Illuminate\Database\Eloquent\Model;

class EntryMgmtHist extends Model
{
  protected $table = 'entry_mgmt_hist';
  const UPDATED_AT = null;

  /**
   * The attributes that are mass assignable.
   *
   * @var string[]
   */
  protected $fillable = [
    'entry_mgmt_id',
    'name',
    'slug',
    'status',
    'is_display',
    'rank_order',
    'layout_structure',
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
    'entry_mgmt_id' => 'integer',
    'name' => 'string',
    'slug' => 'string',
    'status' => 'integer',
    'is_display' => 'boolean',
    'rank_order' => 'integer',
    'layout_structure' => 'array',
    'action' => 'integer',
    'author_id' => 'integer',
    'created_at' => 'datetime',
  ];

  public function entryMgmt()
  {
    return $this->belongsTo(\App\Models\Management\EntryMgmt::class, 'entry_mgmt_id');
  }

  public function author()
  {
    return $this->belongsTo(\App\Models\Master\AdminMst::class, 'author_id');
  }
}
