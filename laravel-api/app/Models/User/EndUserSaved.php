<?php

declare(strict_types=1);

namespace App\Models\User;

use App\Enums\SavedKind;
use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One article an end user saved as a favourite or to read later, or a category saved as a
 * favourite (ADR-0020, ADR-0024). A read-later article keeps the anchor of the heading the reader
 * reached and when.
 *
 * @property int $id
 * @property int|null $article_id
 * @property int|null $content_category_id
 * @property string|null $reading_anchor
 * @property Carbon|null $read_at
 * @property-read Article|null $article
 * @property-read ContentCategory|null $category
 */
class EndUserSaved extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'end_user_saved';

    protected $fillable = ['end_user_id', 'kind', 'article_id', 'content_category_id', 'reading_anchor', 'read_at'];

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * @return BelongsTo<ContentCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'content_category_id');
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'end_user_id' => 'integer',
            'kind' => SavedKind::class,
            'article_id' => 'integer',
            'content_category_id' => 'integer',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
