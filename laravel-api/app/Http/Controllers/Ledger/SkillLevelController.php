<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ledger;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ledger\SkillLevel\ListSkillLevelRequest;
use App\Http\Requests\Ledger\SkillLevel\StoreSkillLevelRequest;
use App\Http\Resources\Ledger\SkillLevelResource;
use App\Services\Ledger\SkillLevelService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Level history of one skill, newest first. Append-only: no update or delete route.
 */
class SkillLevelController extends Controller
{
    public function __construct(private readonly SkillLevelService $levels) {}

    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, SkillLevelResource>>
     */
    public function list(ListSkillLevelRequest $request): AnonymousResourceCollection
    {
        return $this->levels->list($request->validated());
    }

    /**
     * Record a level change; open goals reached by it become achieved
     */
    public function store(StoreSkillLevelRequest $request): int
    {
        return $this->levels->record($request->validated());
    }
}
