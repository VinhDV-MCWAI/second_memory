<?php

declare(strict_types=1);

namespace App\Http\Requests\Audit;

use App\Constants\CommonVal;
use App\Enums\AuditEvent;
use App\Http\Requests\ListRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ListAuditLogRequest extends ListRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filters(): array
    {
        return [
            'auditable_type' => ['nullable', 'string', 'max:50'],
            'auditable_id' => ['nullable', 'integer', 'min:'.CommonVal::MIN_INTEGER],
            'event' => ['nullable', Rule::enum(AuditEvent::class)],
            'admin_mst_id' => ['nullable', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'from_date' => ['nullable', 'date_format:'.CommonVal::DATE_FORMAT, 'after_or_equal:'.CommonVal::MIN_DATE, 'before_or_equal:'.CommonVal::MAX_DATE],
            'to_date' => ['nullable', 'date_format:'.CommonVal::DATE_FORMAT, 'after_or_equal:'.CommonVal::MIN_DATE, 'before_or_equal:'.CommonVal::MAX_DATE, 'after_or_equal:from_date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'admin_mst_id' => __('messages.author_id'),
            'from_date' => __('messages.from_date'),
            'to_date' => __('messages.to_date'),
        ];
    }
}
