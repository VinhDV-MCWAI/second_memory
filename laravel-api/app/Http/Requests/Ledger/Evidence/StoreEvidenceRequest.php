<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\Evidence;

use App\Constants\LedgerConst;
use App\Enums\EvidenceType;
use App\Models\Ledger\Skill;
use App\Models\Ledger\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Evidence is a link only (ADR-0007). `source`, `external_key` and `unpublished_at` belong
 * to the importer (RFC-002 §4.5) and are not accepted here.
 */
class StoreEvidenceRequest extends FormRequest
{
    /** `required` on store; UpdateEvidenceRequest turns it into `sometimes`. */
    protected string $presence = 'required';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [$this->presence, Rule::enum(EvidenceType::class)],
            'title' => [$this->presence, 'string', 'max:'.LedgerConst::EVIDENCE_TITLE_MAX],
            // http(s) only: blocks javascript: and data: links on the public page (REQ-002 US-2)
            'url' => [$this->presence, 'string', 'url:http,https', 'max:'.LedgerConst::EVIDENCE_URL_MAX],
            'occurred_on' => [$this->presence, 'date_format:'.LedgerConst::DATE_FORMAT],
            'summary' => ['nullable', 'string', 'max:'.LedgerConst::EVIDENCE_SUMMARY_MAX],
            'is_public' => ['sometimes', 'boolean'],
            'skill_ids' => [$this->presence, 'array', 'min:1'],
            'skill_ids.*' => ['integer', 'distinct', Rule::exists(Skill::class, 'id')],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'distinct', Rule::exists(Tag::class, 'id')],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => __('messages.type'),
            'title' => __('messages.title'),
            'url' => __('messages.url'),
            'occurred_on' => __('messages.occurred_on'),
            'summary' => __('messages.summary'),
            'is_public' => __('messages.is_public'),
            'skill_ids' => __('messages.skill_ids'),
            'tag_ids' => __('messages.tag_ids'),
        ];
    }
}
