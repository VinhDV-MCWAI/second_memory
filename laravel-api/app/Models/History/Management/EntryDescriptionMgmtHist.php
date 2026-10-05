<?php

namespace App\Models\History\Management;

use Illuminate\Database\Eloquent\Model;

class EntryDescriptionMgmtHist extends Model
{
  protected $table = 'entry_description_mgmt_hist';
  const UPDATED_AT = null;

  /**
   * The attributes that are mass assignable.
   *
   * @var string[]
   */
  protected $fillable = [
    'entry_description_mgmt_id',
    'title',
    'summary',
    'article',
    'status',
    'is_display',
    'rank_order',
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
    'entry_description_mgmt_id' => 'integer',
    'title' => 'string',
    'summary' => 'string',
    'article' => 'string',
    'status' => 'integer',
    'is_display' => 'boolean',
    'rank_order' => 'integer',
    'action' => 'integer',
    'author_id' => 'integer',
    'created_at' => 'datetime',
  ];

  public function entryDescriptionMgmt()
  {
    return $this->belongsTo(\App\Models\Management\EntryDescriptionMgmt::class, 'entry_description_mgmt_id');
  }

  public function author()
  {
    return $this->belongsTo(\App\Models\Master\AdminMst::class, 'author_id');
  }
}
