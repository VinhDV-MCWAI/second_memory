<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\EvidenceImport;

use App\Constants\LedgerConst;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The complete list of published Obsidian notes (RFC-002 §4.5, ADR-0010 importer contract).
 * Skills and tags are names, not ids: unknown skills are reported, missing tags are created.
 */
class ImportEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // An empty list is valid: the vault has no published note left, so every imported row is hidden
            'notes' => ['present', 'array', 'max:'.LedgerConst::IMPORT_NOTES_MAX],
            'notes.*.external_key' => ['required', 'string', 'distinct', 'max:'.LedgerConst::IMPORT_EXTERNAL_KEY_MAX],
            'notes.*.title' => ['required', 'string', 'max:'.LedgerConst::EVIDENCE_TITLE_MAX],
            'notes.*.url' => ['required', 'string', 'url:http,https', 'max:'.LedgerConst::EVIDENCE_URL_MAX],
            'notes.*.occurred_on' => ['required', 'date_format:'.LedgerConst::DATE_FORMAT, 'before_or_equal:today'],
            'notes.*.summary' => ['nullable', 'string', 'max:'.LedgerConst::EVIDENCE_SUMMARY_MAX],
            'notes.*.tags' => ['sometimes', 'array'],
            'notes.*.tags.*' => ['string', 'max:'.LedgerConst::TAG_NAME_MAX],
            'notes.*.skills' => ['sometimes', 'array'],
            'notes.*.skills.*' => ['string', 'max:'.LedgerConst::SKILL_NAME_MAX],
            'dry_run' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'notes' => __('messages.notes'),
            'notes.*.external_key' => __('messages.external_key'),
            'notes.*.title' => __('messages.title'),
            'notes.*.url' => __('messages.url'),
            'notes.*.occurred_on' => __('messages.occurred_on'),
            'notes.*.summary' => __('messages.summary'),
            'notes.*.tags' => __('messages.tag_ids'),
            'notes.*.skills' => __('messages.skill_ids'),
            'dry_run' => __('messages.dry_run'),
        ];
    }
}
