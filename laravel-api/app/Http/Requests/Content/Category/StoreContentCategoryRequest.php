<?php

declare(strict_types=1);

namespace App\Http\Requests\Content\Category;

use App\Constants\ContentConst;
use App\Constants\Messages;
use App\Models\Content\ContentCategory;
use App\Rules\UniqueIgnoringCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A group (no parent) or a category inside one (ADR-0022). `attachments` replaces the list, in
 * display order; an item with an `id` keeps that attachment (and its public key).
 */
class StoreContentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', Rule::exists(ContentCategory::class, 'id')],
            'name' => ['required', 'string', 'max:'.ContentConst::CATEGORY_NAME_MAX, new UniqueIgnoringCase('content_category', 'name', $this->route('id'))],
            'description' => ['nullable', 'string', 'max:'.ContentConst::CATEGORY_DESCRIPTION_MAX],
            'is_public' => ['sometimes', 'boolean'],
            'attachments' => ['sometimes', 'array', 'max:'.ContentConst::ATTACHMENTS_MAX],
            'attachments.*.id' => ['nullable', 'integer'],
            'attachments.*.title' => ['required', 'string', 'max:'.ContentConst::ATTACHMENT_TITLE_MAX],
            'attachments.*.url' => ['nullable', 'required_without:attachments.*.media_id', 'url:http,https', 'max:'.ContentConst::ATTACHMENT_URL_MAX],
            'attachments.*.media_id' => ['nullable', 'integer'],
            'attachments.*.note' => ['nullable', 'string', 'max:'.ContentConst::ATTACHMENT_NOTE_MAX],
        ];
    }

    public function messages(): array
    {
        return ['attachments.*.url.required_without' => Messages::E0042];
    }

    public function attributes(): array
    {
        return [
            'parent_id' => __('messages.parent_id'),
            'name' => __('messages.name'),
            'description' => __('messages.description'),
            'is_public' => __('messages.is_public'),
            'attachments' => __('messages.attachments'),
            'attachments.*.title' => __('messages.title'),
            'attachments.*.url' => __('messages.url'),
            'attachments.*.media_id' => __('messages.media_ids'),
            'attachments.*.note' => __('messages.note'),
        ];
    }
}
