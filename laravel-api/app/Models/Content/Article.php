<?php

declare(strict_types=1);

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An article in a category; the body is Markdown (ADR-0021).
 *
 * @property int $id
 * @property int $content_category_id
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property string $body
 * @property bool $is_public
 */
class Article extends Model
{
    use HasFactory;

    protected $table = 'article';

    protected $fillable = ['content_category_id', 'title', 'slug', 'summary', 'body', 'sort_order', 'is_public'];

    /**
     * @return BelongsTo<ContentCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'content_category_id');
    }

    /**
     * Shown on the public site: the article and its category are both public.
     *
     * @param  Builder<Article>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('article.is_public', true)
            ->whereHas('category', fn (Builder $category) => $category->where('is_public', true));
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'content_category_id' => 'integer',
            'sort_order' => 'integer',
            'is_public' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
