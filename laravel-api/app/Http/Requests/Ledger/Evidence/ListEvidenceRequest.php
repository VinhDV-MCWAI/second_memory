<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\Evidence;

use App\Constants\LedgerConst;
use App\Enums\EvidenceSource;
use App\Enums\EvidenceType;
use App\Http\Requests\ListRequest;
use Illuminate\Validation\Rule;

class ListEvidenceRequest extends ListRequest
{
    protected function filters(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:'.LedgerConst::EVIDENCE_TITLE_MAX],
            'type' => ['nullable', Rule::enum(EvidenceType::class)],
            'source' => ['nullable', Rule::enum(EvidenceSource::class)],
            'is_public' => ['nullable', 'boolean'],
            'skill_id' => ['nullable', 'integer'],
            'tag_id' => ['nullable', 'integer'],
        ];
    }
}
