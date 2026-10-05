<?php

namespace App\Models\Management;

use App\Models\History\Management\CategoryMgmtHist;
use App\Traits\HasSoftDelete;
use App\Traits\HasStatus;
use App\Traits\HasHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryMgmt extends Model
{
  use HasSoftDelete, HasStatus, HasHistory, HasFactory;

  protected $table = 'category_mgmt';

  /**
   * The attributes that are mass assignable.
   *
   * @var string[]
   */
  protected $fillable = [
    'name',
    'slug',
    'description',
    'status',
    'is_display',
    'rank_order',
    'is_delete',
    'layout_structure',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array
   */
  protected $casts = [
    'id' => 'integer',
    'name' => 'string',
    'slug' => 'string',
    'description' => 'string',
    'status' => 'integer',
    'is_display' => 'boolean',
    'rank_order' => 'integer',
    'is_delete' => 'boolean',
    'layout_structure' => 'array',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
  ];

  /**
   * Get the history records for the category.
   *
   * @return HasMany
   */
  public function history(): HasMany
  {
    return $this->hasMany(CategoryMgmtHist::class, 'category_mgmt_id');
  }
}
