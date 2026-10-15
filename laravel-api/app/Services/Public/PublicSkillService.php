<?php

declare(strict_types=1);

namespace App\Services\Public;

use App\Http\Resources\Public\PublicSkillDetailResource;
use App\Http\Resources\Public\PublicSkillResource;
use App\Repositories\Ledger\SkillRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read-only public view of the Skill Ledger (REQ-002 US-3).
 */
final class PublicSkillService
{
    public function __construct(private readonly SkillRepository $skills) {}

    public function list(): AnonymousResourceCollection
    {
        return PublicSkillResource::collection($this->skills->publicSkills());
    }

    /**
     * A private skill throws the same ModelNotFoundException as a missing one, so both are 404.
     */
    public function show(string $slug): PublicSkillDetailResource
    {
        return new PublicSkillDetailResource($this->skills->publicSkillBySlug($slug));
    }
}
