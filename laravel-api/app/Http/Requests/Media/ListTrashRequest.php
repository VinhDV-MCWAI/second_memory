<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Constants\MediaConst;
use App\Http\Requests\ListRequest;

class ListTrashRequest extends ListRequest
{
    protected function filters(): array
    {
        return ['name' => ['nullable', 'string', 'max:'.MediaConst::NAME_MAX]];
    }

    public function attributes(): array
    {
        return ['name' => __('messages.name')];
    }
}
