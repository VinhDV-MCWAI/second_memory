<?php

declare(strict_types=1);

namespace App\Http\Requests\Content\Article;

use App\Constants\ContentConst;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The complete list of published Obsidian notes (ADR-0022, tools/ledger-importer). Categories are
 * names, never created; `parent` and the `[[links]]` of the body name other notes the way Obsidian
 * does (path or file name), and the service resolves them.
 */
class ImportArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // An empty list is valid: the vault has no published note left, so every imported article is hidden
            'notes' => ['present', 'array', 'max:'.ContentConst::IMPORT_NOTES_MAX],
            'notes.*.external_key' => ['required', 'string', 'distinct', 'max:'.ContentConst::IMPORT_EXTERNAL_KEY_MAX],
            'notes.*.title' => ['required', 'string', 'max:'.ContentConst::ARTICLE_TITLE_MAX],
            'notes.*.summary' => ['nullable', 'string', 'max:'.ContentConst::ARTICLE_SUMMARY_MAX],
            'notes.*.body' => ['present', 'nullable', 'string', 'max:'.ContentConst::ARTICLE_BODY_MAX],
            'notes.*.parent' => ['nullable', 'string', 'max:'.ContentConst::IMPORT_EXTERNAL_KEY_MAX],
            'notes.*.categories' => ['sometimes', 'array'],
            'notes.*.categories.*' => ['string', 'max:'.ContentConst::CATEGORY_NAME_MAX],
            'dry_run' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'notes' => __('messages.notes'),
            'notes.*.external_key' => __('messages.external_key'),
            'notes.*.title' => __('messages.title'),
            'notes.*.summary' => __('messages.summary'),
            'notes.*.body' => __('messages.body'),
            'notes.*.parent' => __('messages.parent_note'),
            'notes.*.categories' => __('messages.category_ids'),
            'notes.*.categories.*' => __('messages.category'),
            'dry_run' => __('messages.dry_run'),
        ];
    }
}
