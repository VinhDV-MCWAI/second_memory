<?php

declare(strict_types=1);

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An article: one topic of experience in Markdown (ADR-0022). It belongs to any number of
 * categories and may sit under a parent article, so a book is a tree of articles.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $title
 * @property string $slug
 * @property string|null $summary
 * @property string $body
 * @property int $sort_order
 * @property bool $is_public
 * @property string $public_key
 * @property Carbon|null $updated_at
 */
class Article extends Model
{
    use HasFactory;

    protected $table = 'article';

    protected $fillable = ['parent_id', 'title', 'slug', 'summary', 'body', 'sort_order', 'is_public'];

    /**
     * Rows of the visible() check: true for an article whose parent chain holds a private article.
     * The parent rule keeps chains acyclic and at most ARTICLE_DEPTH_MAX long, so the walk ends.
     */
    private const HIDDEN_BY_ANCESTOR = <<<'SQL'
        EXISTS (
            WITH RECURSIVE up(id) AS (
                SELECT article.parent_id
                UNION ALL
                SELECT ancestor.parent_id FROM article ancestor JOIN up ON ancestor.id = up.id
            )
            SELECT 1 FROM up JOIN article hidden ON hidden.id = up.id WHERE hidden.is_public = false
        )
        SQL;

    /**
     * @return BelongsToMany<ContentCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ContentCategory::class, 'article_category');
    }

    /**
     * @return BelongsTo<Article, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Shown on the public site: the article and every article above it are public.
     *
     * @param  Builder<Article>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('article.is_public', true)->whereRaw('NOT '.self::HIDDEN_BY_ANCESTOR);
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'parent_id' => 'integer',
            'sort_order' => 'integer',
            'is_public' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
