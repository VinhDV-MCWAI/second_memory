<?php

declare(strict_types=1);

namespace App\Services\Management;

use App\Http\Resources\Management\SettingLinkMgmtResource;
use App\Repositories\History\Management\SettingLinkMgmtHistRepository;
use App\Repositories\Management\SettingLinkMgmtRepository;
use App\Services\AuditedCrudService;

class SettingLinkMgmtService extends AuditedCrudService
{
    protected string $resource = SettingLinkMgmtResource::class;

    protected string $historyForeignKey = 'setting_link_mgmt_id';

    public function __construct(SettingLinkMgmtRepository $settingLinkMgmt, SettingLinkMgmtHistRepository $settingLinkMgmtHist)
    {
        parent::__construct($settingLinkMgmt, $settingLinkMgmtHist);
    }
}
