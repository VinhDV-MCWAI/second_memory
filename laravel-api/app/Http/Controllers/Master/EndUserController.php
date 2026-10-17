<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\EndUser\DeleteEndUserRequest;
use App\Http\Requests\Master\EndUser\ListEndUserRequest;
use App\Http\Requests\Master\EndUser\UpdateEndUserRequest;
use App\Http\Requests\Master\EndUser\UpdateRegistrationRequest;
use App\Http\Resources\Master\EndUserResource;
use App\Services\Master\EndUserService;
use App\Services\System\RegistrationSetting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * End users of the public site (ADR-0020). They sign up themselves, so there is no store route.
 */
class EndUserController extends Controller
{
    public function __construct(
        private readonly EndUserService $endUsers,
        private readonly RegistrationSetting $registration,
    ) {}

    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, EndUserResource>>
     */
    public function list(ListEndUserRequest $request): AnonymousResourceCollection
    {
        return $this->endUsers->list($request->validated());
    }

    /**
     * Lock / unlock; a non-empty `password` replaces the end user's password.
     */
    public function update(UpdateEndUserRequest $request, string $id): int
    {
        return $this->endUsers->update([...$request->validated(), 'id' => $id]);
    }

    public function delete(DeleteEndUserRequest $request): void
    {
        $this->endUsers->delete($request->validated());
    }

    /**
     * @return array{open: bool}
     */
    public function registration(): array
    {
        return ['open' => $this->registration->isOpen()];
    }

    /**
     * Open or close sign-up on the public site.
     */
    public function updateRegistration(UpdateRegistrationRequest $request): void
    {
        $this->registration->setOpen($request->boolean('open'));
    }
}
