<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Constants\MediaConst;
use App\Http\Requests\ListRequest;

class ListMediaRequest extends ListRequest
{
    protected function filters(): array
    {
        return [
            'folder' => MediaRules::folder(),
            'name' => ['nullable', 'string', 'max:'.MediaConst::NAME_MAX],
        ];
    }

    public function attributes(): array
    {
        return ['folder' => __('messages.folder'), 'name' => __('messages.name')];
    }
}
