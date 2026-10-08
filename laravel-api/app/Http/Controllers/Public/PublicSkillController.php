<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\PublicSkillDetailResource;
use App\Http\Resources\Public\PublicSkillResource;
use App\Services\Public\PublicSkillService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * No login. GET only, rate-limited per IP (RFC-002 §4.3).
 */
class PublicSkillController extends Controller
{
    public function __construct(private readonly PublicSkillService $skills) {}

    /**
     * Public skills, by name
     *
     * @unauthenticated
     *
     * @return AnonymousResourceCollection<int, PublicSkillResource>
     */
    public function list(): AnonymousResourceCollection
    {
        return $this->skills->list();
    }

    /**
     * One public skill with its level history dates and public evidence; private or missing → 404
     *
     * @unauthenticated
     */
    public function show(string $slug): PublicSkillDetailResource
    {
        return $this->skills->show($slug);
    }
}
