<?php

declare(strict_types=1);

namespace App\Models\Ledger;

use App\Enums\SkillLevel as SkillLevelEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A skill with an append-only level history (RFC-002 §4.2).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_public
 * @property SkillLevelEnum $current_level
 */
class Skill extends Model
{
    use HasFactory;

    protected $table = 'skill';

    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'is_public',
        'current_level',
    ];

    /**
     * @return HasMany<SkillLevel, $this>
     */
    public function levels(): HasMany
    {
        return $this->hasMany(SkillLevel::class);
    }

    /**
     * @return HasMany<LearningGoal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(LearningGoal::class);
    }

    /**
     * @return BelongsToMany<Evidence, $this>
     */
    public function evidence(): BelongsToMany
    {
        return $this->belongsToMany(Evidence::class, 'evidence_skill');
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'skill_tag');
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'is_public' => 'boolean',
            'current_level' => SkillLevelEnum::class,
        ];
    }
}
