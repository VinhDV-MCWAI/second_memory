<?php

namespace App\Models\Master;

use App\Traits\HasSoftDelete;
use App\Traits\HasStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FeatureMst extends Model
{
  use HasSoftDelete, HasStatus, HasFactory;

  protected $table = 'feature_mst';

  /**
   * The attributes that are mass assignable.
   *
   * @var string[]
   */
  protected $fillable = [
    'name',
    'group_name',
    'status',
    'is_delete',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array
   */
  protected $casts = [
    'id' => 'integer',
    'name' => 'string',
    'group_name' => 'string',
    'status' => 'integer',
    'is_delete' => 'boolean',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
  ];

  /**
   * Get the APIs for the feature.
   *
   * @return HasMany
   */
  public function apis(): HasMany
  {
    return $this->hasMany(ApiMst::class, 'feature_mst_id');
  }
}
