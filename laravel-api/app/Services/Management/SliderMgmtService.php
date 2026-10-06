<?php

declare(strict_types=1);

namespace App\Services\Management;

use App\Http\Resources\Management\SliderMgmtResource;
use App\Repositories\History\Management\SliderMgmtHistRepository;
use App\Repositories\Management\SliderMgmtRepository;
use App\Services\AuditedCrudService;

class SliderMgmtService extends AuditedCrudService
{
    protected string $resource = SliderMgmtResource::class;

    protected string $historyForeignKey = 'slider_mgmt_id';

    public function __construct(SliderMgmtRepository $sliderMgmt, SliderMgmtHistRepository $sliderMgmtHist)
    {
        parent::__construct($sliderMgmt, $sliderMgmtHist);
    }
}
