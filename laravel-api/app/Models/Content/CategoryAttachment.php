<?php

declare(strict_types=1);

namespace App\Models\Content;

use App\Models\Media\MediaMgmt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Evidence of a category (ADR-0022): a link, a file of the file manager, or both.
 *
 * @property int $id
 * @property int $content_category_id
 * @property string $title
 * @property string|null $url
 * @property int|null $media_id
 * @property string|null $note
 * @property int $position
 * @property string $public_id
 * @property-read MediaMgmt|null $media
 */
class CategoryAttachment extends Model
{
    protected $table = 'category_attachment';

    protected $fillable = ['content_category_id', 'title', 'url', 'media_id', 'note', 'position'];

    /**
     * @return BelongsTo<ContentCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class, 'content_category_id');
    }

    /**
     * @return BelongsTo<MediaMgmt, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaMgmt::class, 'media_id');
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'content_category_id' => 'integer',
            'media_id' => 'integer',
            'position' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
