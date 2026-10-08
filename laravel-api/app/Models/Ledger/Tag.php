<?php

declare(strict_types=1);

namespace App\Models\Ledger;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'tag';

    protected $fillable = ['name'];

    /**
     * @return BelongsToMany<Skill, $this>
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'skill_tag');
    }

    /**
     * @return BelongsToMany<Evidence, $this>
     */
    public function evidence(): BelongsToMany
    {
        return $this->belongsToMany(Evidence::class, 'evidence_tag');
    }

    protected function casts(): array
    {
        return ['id' => 'integer'];
    }
}
