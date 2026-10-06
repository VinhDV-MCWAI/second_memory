<?php

namespace App\Services\Management;

use App\Http\Resources\Management\CategoryMgmtResource;
use App\Repositories\History\Management\CategoryMgmtHistRepository;
use App\Repositories\Management\CategoryMgmtRepository;
use App\Services\AuditedCrudService;

class CategoryMgmtService extends AuditedCrudService
{
    protected string $resource = CategoryMgmtResource::class;

    protected string $historyForeignKey = 'category_mgmt_id';

    public function __construct(CategoryMgmtRepository $categoryMgmt, CategoryMgmtHistRepository $categoryMgmtHist)
    {
        parent::__construct($categoryMgmt, $categoryMgmtHist);
    }
}
