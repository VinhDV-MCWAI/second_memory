<?php

namespace App\Services\History\Management;

use App\Http\Resources\History\Management\CategoryMgmtHistResource;
use App\Repositories\History\Management\CategoryMgmtHistRepository;
use App\Services\CrudService;

class CategoryMgmtHistService extends CrudService
{
    protected string $resource = CategoryMgmtHistResource::class;

    public function __construct(CategoryMgmtHistRepository $categoryMgmtHist)
    {
        parent::__construct($categoryMgmtHist);
    }
}
