<?php

declare(strict_types=1);

namespace Tests\Feature\Requests;

use App\Enums\IsActive;
use App\Http\Requests\History\Management\CategoryMgmtHist\ListCategoryMgmtHistRequest;
use App\Http\Requests\History\Management\EntryDescriptionMgmtHist\StoreEntryDescriptionMgmtHistRequest;
use App\Http\Requests\History\Management\EntryDescriptionMgmtHist\UpdateEntryDescriptionMgmtHistRequest;
use App\Http\Requests\History\Management\EntryMgmtHist\StoreEntryMgmtHistRequest;
use App\Http\Requests\History\Management\EntryMgmtHist\UpdateEntryMgmtHistRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class HistoryIsDisplayRuleTest extends TestCase
{
    /**
     * @return array<string, array{class-string<FormRequest>}>
     */
    public static function requests(): array
    {
        return [
            'category hist list' => [ListCategoryMgmtHistRequest::class],
            'entry description hist store' => [StoreEntryDescriptionMgmtHistRequest::class],
            'entry description hist update' => [UpdateEntryDescriptionMgmtHistRequest::class],
            'entry hist store' => [StoreEntryMgmtHistRequest::class],
            'entry hist update' => [UpdateEntryMgmtHistRequest::class],
        ];
    }

    /**
     * @param  class-string<FormRequest>  $requestClass
     */
    #[DataProvider('requests')]
    public function test_is_display_accepts_is_active_values(string $requestClass): void
    {
        $rules = (new $requestClass)->rules();

        foreach (IsActive::cases() as $case) {
            $validator = Validator::make(['is_display' => $case->value], ['is_display' => $rules['is_display']]);
            $this->assertTrue($validator->passes(), "is_display={$case->value} rejected by {$requestClass}");
        }

        $validator = Validator::make(['is_display' => 9], ['is_display' => $rules['is_display']]);
        $this->assertTrue($validator->fails());
    }
}
