<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\Search;

use App\Constants\LedgerConst;
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
            'q' => ['required', 'string', 'min:'.LedgerConst::SEARCH_QUERY_MIN, 'max:'.LedgerConst::SEARCH_QUERY_MAX],
        ];
    }

    public function attributes(): array
    {
        return ['q' => __('messages.search_query')];
    }
}
