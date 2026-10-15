<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\Evidence;

/**
 * Same fields as store, each optional. Imported rows accept only `type` and `is_public`
 * (EvidenceService::update).
 */
class UpdateEvidenceRequest extends StoreEvidenceRequest
{
    protected string $presence = 'sometimes';
}
