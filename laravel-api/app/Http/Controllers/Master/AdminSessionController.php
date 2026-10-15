<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\AdminSession\EndAdminSessionRequest;
use App\Http\Resources\Master\AdminSessionResource;
use App\Services\Master\AdminSessionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Sessions screen (AUTH-04): the signed-in admin's own devices. Every admin may use it (ALWAYS_ALLOWED).
 */
final class AdminSessionController extends Controller
{
    public function __construct(private readonly AdminSessionService $sessions) {}

    /**
     * Devices I am signed in on, most recently used first
     */
    public function list(): AnonymousResourceCollection
    {
        return AdminSessionResource::collection($this->sessions->list((int) Auth::id()));
    }

    /**
     * Sign out other devices; returns how many sessions ended. The session in use ends with log out (422).
     *
     * @throws ValidationException
     */
    public function delete(EndAdminSessionRequest $request): int
    {
        /** @var list<int> $ids */
        $ids = array_map('intval', $request->validated('ids'));

        return $this->sessions->end((int) Auth::id(), $ids, $request->session()->getId());
    }

    /**
     * Sign out every device except this one; returns how many sessions ended
     */
    public function deleteOthers(Request $request): int
    {
        return $this->sessions->endAll((int) Auth::id(), $request->session()->getId());
    }
}
