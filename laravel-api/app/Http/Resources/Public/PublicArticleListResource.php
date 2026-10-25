<?php

declare(strict_types=1);

namespace App\Http\Resources\Public;

use App\Models\Content\Article;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One page of every shown article in the order asked for: the tabs of the home page (ADR-0024).
 *
 * @mixin LengthAwarePaginator<int, Article>
 */
class PublicArticleListResource extends JsonResource
{
    /**
     * @param  LengthAwarePaginator<int, Article>  $articles
     */
    public function __construct(private readonly LengthAwarePaginator $articles)
    {
        parent::__construct($articles);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'articles' => PublicArticleCardResource::collection($this->articles->items()),
            'page' => $this->articles->currentPage(),
            'last_page' => $this->articles->lastPage(),
            'total' => $this->articles->total(),
        ];
    }
}
