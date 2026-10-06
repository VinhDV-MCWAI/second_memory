<?php

declare(strict_types=1);

namespace App\Services\History\Management;

use App\Http\Resources\History\Management\SliderMgmtHistResource;
use App\Repositories\History\Management\SliderMgmtHistRepository;
use App\Services\CrudService;

class SliderMgmtHistService extends CrudService
{
    protected string $resource = SliderMgmtHistResource::class;

    public function __construct(SliderMgmtHistRepository $sliderMgmtHist)
    {
        parent::__construct($sliderMgmtHist);
    }
}
