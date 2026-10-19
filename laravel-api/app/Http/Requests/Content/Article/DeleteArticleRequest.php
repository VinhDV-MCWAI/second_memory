<?php

declare(strict_types=1);

namespace App\Http\Requests\Content\Article;

use App\Models\Content\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', Rule::exists(Article::class, 'id')],
        ];
    }

    public function attributes(): array
    {
        return ['ids' => __('messages.ids'), 'ids.*' => __('messages.ids')];
    }
}
