<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ledger;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ledger\Skill\DeleteSkillRequest;
use App\Http\Requests\Ledger\Skill\ListSkillRequest;
use App\Http\Requests\Ledger\Skill\StoreSkillRequest;
use App\Http\Requests\Ledger\Skill\UpdateSkillRequest;
use App\Http\Resources\Ledger\SkillResource;
use App\Services\Ledger\SkillService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SkillController extends Controller
{
    public function __construct(private readonly SkillService $skills) {}

    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, SkillResource>>
     */
    public function list(ListSkillRequest $request): AnonymousResourceCollection
    {
        return $this->skills->list($request->validated());
    }

    /**
     * Create a skill with its first level entry
     */
    public function store(StoreSkillRequest $request): int
    {
        return $this->skills->store($request->validated());
    }

    /**
     * Update a skill (its level changes through skill-level/store)
     */
    public function update(UpdateSkillRequest $request, string $id): int
    {
        return $this->skills->update([...$request->validated(), 'id' => $id]);
    }

    public function delete(DeleteSkillRequest $request): void
    {
        $this->skills->delete($request->validated());
    }
}
