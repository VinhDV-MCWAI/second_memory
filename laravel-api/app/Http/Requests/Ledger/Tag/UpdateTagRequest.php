<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\Tag;

use App\Constants\LedgerConst;
use App\Rules\UniqueIgnoringCase;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.LedgerConst::TAG_NAME_MAX, new UniqueIgnoringCase('tag', 'name', $this->route('id'))],
        ];
    }

    public function attributes(): array
    {
        return ['name' => __('messages.name')];
    }
}
