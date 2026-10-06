<?php

declare(strict_types=1);

namespace App\Services\Master;

use App\Http\Resources\Master\FeatureMstResource;
use App\Repositories\History\Master\FeatureMstHistRepository;
use App\Repositories\Master\FeatureMstRepository;
use App\Services\AuditedCrudService;

class FeatureMstService extends AuditedCrudService
{
    protected string $resource = FeatureMstResource::class;

    protected string $historyForeignKey = 'feature_mst_id';

    public function __construct(FeatureMstRepository $featureMst, FeatureMstHistRepository $featureMstHist)
    {
        parent::__construct($featureMst, $featureMstHist);
    }
}
