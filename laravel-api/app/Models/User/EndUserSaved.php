<?php

declare(strict_types=1);

namespace App\Models\User;

use App\Enums\SavedKind;
use App\Models\Content\Article;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One article an end user saved as a favourite or to read later (ADR-0020).
 */
class EndUserSaved extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'end_user_saved';

    protected $fillable = ['end_user_id', 'kind', 'article_id'];

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'end_user_id' => 'integer',
            'kind' => SavedKind::class,
            'article_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
