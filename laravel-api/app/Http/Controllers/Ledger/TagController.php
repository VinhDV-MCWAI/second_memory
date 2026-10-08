<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ledger;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ledger\Tag\DeleteTagRequest;
use App\Http\Requests\Ledger\Tag\ListTagRequest;
use App\Http\Requests\Ledger\Tag\StoreTagRequest;
use App\Http\Requests\Ledger\Tag\UpdateTagRequest;
use App\Http\Resources\Ledger\TagResource;
use App\Services\Ledger\TagService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    public function __construct(private readonly TagService $tags) {}

    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, TagResource>>
     */
    public function list(ListTagRequest $request): AnonymousResourceCollection
    {
        return $this->tags->list($request->validated());
    }

    public function store(StoreTagRequest $request): int
    {
        return $this->tags->store($request->validated());
    }

    public function update(UpdateTagRequest $request, string $id): int
    {
        return $this->tags->update([...$request->validated(), 'id' => $id]);
    }

    public function delete(DeleteTagRequest $request): void
    {
        $this->tags->delete($request->validated());
    }
}
