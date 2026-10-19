<?php

declare(strict_types=1);

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A category of the public site's home page, e.g. "Thiết kế" (ADR-0021).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_public
 */
class ContentCategory extends Model
{
    use HasFactory;

    protected $table = 'content_category';

    protected $fillable = ['name', 'slug', 'description', 'sort_order', 'is_public'];

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'sort_order' => 'integer',
            'is_public' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
