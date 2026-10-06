<?php

namespace App\Services\History\Master;

use App\Http\Resources\History\Master\FeatureMstHistResource;
use App\Repositories\History\Master\FeatureMstHistRepository;
use App\Services\CrudService;

class FeatureMstHistService extends CrudService
{
    protected string $resource = FeatureMstHistResource::class;

    public function __construct(FeatureMstHistRepository $featureMstHist)
    {
        parent::__construct($featureMstHist);
    }
}
