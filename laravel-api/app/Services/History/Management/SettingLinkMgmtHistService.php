<?php

declare(strict_types=1);

namespace App\Services\History\Management;

use App\Http\Resources\History\Management\SettingLinkMgmtHistResource;
use App\Repositories\History\Management\SettingLinkMgmtHistRepository;
use App\Services\CrudService;

class SettingLinkMgmtHistService extends CrudService
{
    protected string $resource = SettingLinkMgmtHistResource::class;

    public function __construct(SettingLinkMgmtHistRepository $settingLinkMgmtHist)
    {
        parent::__construct($settingLinkMgmtHist);
    }
}
