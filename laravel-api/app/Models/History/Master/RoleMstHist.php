<?php

namespace App\Models\History\Master;

use Illuminate\Database\Eloquent\Model;

class RoleMstHist extends Model
{
  protected $table = 'role_mst_hist';
  const UPDATED_AT = null;

  /**
   * The attributes that are mass assignable.
   *
   * @var string[]
   */
  protected $fillable = [
    'role_mst_id',
    'name',
    'permission',
    'is_active',
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
    'role_mst_id' => 'integer',
    'name' => 'string',
    'permission' => 'string',
    'is_active' => 'boolean',
    'action' => 'integer',
    'author_id' => 'integer',
    'created_at' => 'datetime',
  ];

  public function roleMst()
  {
    return $this->belongsTo(\App\Models\Master\RoleMst::class, 'role_mst_id');
  }

  public function author()
  {
    return $this->belongsTo(\App\Models\Master\AdminMst::class, 'author_id');
  }
}
