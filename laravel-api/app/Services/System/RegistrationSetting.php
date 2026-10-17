<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Constants\EndUserConst;
use App\Enums\AuditEvent;
use App\Repositories\System\AppSettingRepository;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;

/**
 * Whether visitors may create an account on the public site (ADR-0020). Open until the owner closes it.
 */
final class RegistrationSetting
{
    public function __construct(
        private readonly AppSettingRepository $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function isOpen(): bool
    {
        return (bool) $this->settings->get(EndUserConst::SETTING_REGISTRATION_OPEN, true);
    }

    public function setOpen(bool $open): void
    {
        $before = $this->isOpen();
        $this->settings->set(EndUserConst::SETTING_REGISTRATION_OPEN, $open, Auth::id());
        if ($before !== $open) {
            $this->auditLogger->record(
                EndUserConst::SETTING_AUDITABLE_TYPE,
                null,
                AuditEvent::UPDATED,
                ['value' => $before],
                ['value' => $open],
            );
        }
    }
}
