<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\Tag;

use App\Constants\LedgerConst;
use App\Http\Requests\ListRequest;

class ListTagRequest extends ListRequest
{
    protected function filters(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:'.LedgerConst::TAG_NAME_MAX],
        ];
    }
}
