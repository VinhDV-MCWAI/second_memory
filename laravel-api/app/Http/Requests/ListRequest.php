<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base for `{resource}/list` requests: paging, sorting and the id filter are shared,
 * each endpoint declares its own filters(). Unknown sort columns/orders fall back to
 * defaults in BaseRepository::applySorting().
 */
abstract class ListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1'],
            'sort_by' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'string'],
            ...$this->filters(),
        ];
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    abstract protected function filters(): array;
}
