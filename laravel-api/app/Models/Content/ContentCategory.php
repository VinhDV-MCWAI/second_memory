<?php

declare(strict_types=1);

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A group (no parent, e.g. "Kỹ năng") or a category inside one (e.g. "Laravel") of the public site
 * (ADR-0022). The description is Markdown; attachments are its evidence: links or files.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_public
 * @property-read ContentCategory|null $parent
 */
class ContentCategory extends Model
{
    use HasFactory;

    protected $table = 'content_category';

    protected $fillable = ['parent_id', 'name', 'slug', 'description', 'sort_order', 'is_public'];

    /**
     * @return BelongsTo<ContentCategory, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<ContentCategory, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsToMany<Article, $this>
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_category');
    }

    /**
     * @return HasMany<CategoryAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(CategoryAttachment::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Shown on the public site: the category and its group (if any) are both public.
     *
     * @param  Builder<ContentCategory>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('content_category.is_public', true)
            ->where(fn (Builder $top) => $top->whereNull('content_category.parent_id')
                ->orWhereHas('parent', fn (Builder $parent) => $parent->where('is_public', true)));
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
