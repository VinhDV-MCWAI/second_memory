<?php

declare(strict_types=1);

namespace App\Models\Ledger;

use App\Enums\EvidenceSource;
use App\Enums\EvidenceType;
use App\Models\Media\MediaMgmt;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * A link that proves one or more skills (REQ-002 US-2), with files of the file manager attached (LEDGER-01).
 *
 * @property int $id
 * @property EvidenceType $type
 * @property string $title
 * @property string $url
 * @property Carbon $occurred_on
 * @property string|null $summary
 * @property EvidenceSource $source
 * @property bool $is_public
 * @property Carbon|null $unpublished_at
 */
class Evidence extends Model
{
    use HasFactory;

    protected $table = 'evidence';

    protected $fillable = [
        'type',
        'title',
        'url',
        'occurred_on',
        'summary',
        'is_public',
        'source',
        'external_key',
        'unpublished_at',
    ];

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'evidence_skill');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'evidence_tag');
    }

    /**
     * Attached files in the order they were picked, deleted ones included (LEDGER-01).
     *
     * @return BelongsToMany<MediaMgmt, $this, Pivot, 'pivot'>
     */
    public function files(): BelongsToMany
    {
        return $this->belongsToMany(MediaMgmt::class, 'evidence_media', 'evidence_id', 'media_id')
            ->withPivot('public_id')
            ->orderByPivot('position');
    }

    /**
     * Attached files that can be opened: not deleted, fully uploaded.
     *
     * @return BelongsToMany<MediaMgmt, $this, Pivot, 'pivot'>
     */
    public function availableFiles(): BelongsToMany
    {
        return $this->files()->available();
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'type' => EvidenceType::class,
            'source' => EvidenceSource::class,
            'occurred_on' => 'date',
            'is_public' => 'boolean',
            'unpublished_at' => 'datetime',
        ];
    }
}
