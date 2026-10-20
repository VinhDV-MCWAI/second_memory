<?php

declare(strict_types=1);

namespace App\Http\Requests\Content\Search;

use App\Constants\SearchConst;
use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:'.SearchConst::QUERY_MIN, 'max:'.SearchConst::QUERY_MAX],
        ];
    }

    public function attributes(): array
    {
        return ['q' => __('messages.search_query')];
    }
}
