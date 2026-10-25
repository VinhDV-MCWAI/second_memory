<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Enums\SavedKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ListSavedRequest;
use App\Http\Requests\User\ReadingPositionRequest;
use App\Http\Requests\User\SavedRequest;
use App\Http\Resources\User\SavedItemResource;
use App\Models\User\EndUser;
use App\Services\User\EndUserSavedService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Favourites and read later of the signed-in end user (ADR-0020, ADR-0024). Only what the public
 * site shows can be saved (else 404) or listed.
 */
final class SavedController extends Controller
{
    public function __construct(private readonly EndUserSavedService $saved) {}

    /**
     * Newest first.
     *
     * @return AnonymousResourceCollection<int, SavedItemResource>
     */
    public function list(ListSavedRequest $request): AnonymousResourceCollection
    {
        $kind = $request->validated('kind');

        return $this->saved->list($this->endUserId($request), $kind === null ? null : SavedKind::from((string) $kind));
    }

    /**
     * Save an article (`article`: its public `key`) or, as a favourite, a category (`category`: its slug). Saving twice is fine.
     */
    public function store(SavedRequest $request): void
    {
        /** @var array{kind: string, article?: string|null, category?: string|null} $payload */
        $payload = $request->validated();
        $this->saved->save($this->endUserId($request), $payload);
    }

    /**
     * Remove it from that list (also succeeds when it was not saved).
     */
    public function delete(SavedRequest $request): void
    {
        /** @var array{kind: string, article?: string|null, category?: string|null} $payload */
        $payload = $request->validated();
        $this->saved->remove($this->endUserId($request), $payload);
    }

    /**
     * Keep the heading the reader reached in a read-later article (`anchor`: a `toc[].id` of it; empty = the top). Not in the
     * read-later list → 404.
     */
    public function position(ReadingPositionRequest $request): void
    {
        /** @var array{article: string, anchor?: string|null} $payload */
        $payload = $request->validated();
        $this->saved->setReadingPosition($this->endUserId($request), $payload);
    }

    private function endUserId(Request $request): int
    {
        /** @var EndUser $endUser */
        $endUser = $request->user();

        return $endUser->id;
    }
}
