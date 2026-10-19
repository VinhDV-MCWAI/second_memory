<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Enums\SavedKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ListSavedRequest;
use App\Http\Requests\User\SavedRequest;
use App\Http\Resources\User\SavedItemResource;
use App\Models\User\EndUser;
use App\Services\User\EndUserSavedService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Favourites and read later of the signed-in end user (ADR-0020). Only what the public site shows
 * can be saved (else 404) or listed.
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
     * Save a skill (`skill`: slug), an evidence (`evidence`) or an article (`article`): their public `key`. Saving twice is fine.
     */
    public function store(SavedRequest $request): void
    {
        /** @var array{kind: string, skill?: string, evidence?: string, article?: string} $payload */
        $payload = $request->validated();
        $this->saved->save($this->endUserId($request), $payload);
    }

    /**
     * Remove it from that list (also succeeds when it was not saved).
     */
    public function delete(SavedRequest $request): void
    {
        /** @var array{kind: string, skill?: string, evidence?: string, article?: string} $payload */
        $payload = $request->validated();
        $this->saved->remove($this->endUserId($request), $payload);
    }

    private function endUserId(Request $request): int
    {
        /** @var EndUser $endUser */
        $endUser = $request->user();

        return $endUser->id;
    }
}
