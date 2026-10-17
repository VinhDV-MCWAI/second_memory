<?php

declare(strict_types=1);

namespace App\Models\User;

use App\Enums\SavedKind;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\Skill;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One skill or one evidence an end user saved as a favourite or to read later (ADR-0020).
 */
class EndUserSaved extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'end_user_saved';

    protected $fillable = ['end_user_id', 'kind', 'skill_id', 'evidence_id'];

    /**
     * @return BelongsTo<Skill, $this>
     */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    /**
     * @return BelongsTo<Evidence, $this>
     */
    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Evidence::class);
    }

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'end_user_id' => 'integer',
            'kind' => SavedKind::class,
            'skill_id' => 'integer',
            'evidence_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
