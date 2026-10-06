<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LayoutStructureRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param  int  $maxItems  Maximum total items allowed (including nested)
     * @param  string  $childIdField  Field name for child ID (entry_mgmt_id or entry_desc_id)
     */
    public function __construct(protected int $maxItems = 100, protected string $childIdField = 'entry_mgmt_id') {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // If null or empty, it's valid
        if (empty($value)) {
            return;
        }

        // Decode JSON
        $structure = is_string($value) ? json_decode($value, true) : $value;

        if (json_last_error() !== JSON_ERROR_NONE) {
            $fail("The {$attribute} must be valid JSON.");

            return;
        }

        if (! is_array($structure)) {
            $fail("The {$attribute} must be an array.");

            return;
        }

        // Count total items
        $totalItems = $this->countItems($structure);

        if ($totalItems > $this->maxItems) {
            $fail("The {$attribute} cannot contain more than {$this->maxItems} items in total.");

            return;
        }

        // Validate structure
        $validationError = $this->validateStructure($structure);
        if ($validationError) {
            $fail($validationError);
        }
    }

    /**
     * Count total items recursively
     */
    protected function countItems(array $structure): int
    {
        $count = 0;

        foreach ($structure as $item) {
            $count++; // Count this item

            // Count children recursively
            if (isset($item['children']) && is_array($item['children'])) {
                $count += $this->countItems($item['children']);
            }
        }

        return $count;
    }

    /**
     * Validate structure recursively
     */
    protected function validateStructure(array $structure, int $level = 0): ?string
    {
        foreach ($structure as $index => $item) {
            if (! is_array($item)) {
                return "Item at index {$index} must be an array.";
            }

            // Validate required field: ui_id
            if (! isset($item['ui_id']) || ! is_string($item['ui_id']) || empty($item['ui_id'])) {
                return "Item at index {$index} must have a non-empty 'ui_id' field.";
            }

            // Validate child ID field (entry_mgmt_id or entry_desc_id)
            if (! isset($item[$this->childIdField])) {
                return "Item at index {$index} must have a '{$this->childIdField}' field.";
            }

            if (! is_int($item[$this->childIdField]) && ! is_numeric($item[$this->childIdField])) {
                return "Item at index {$index}: '{$this->childIdField}' must be a number.";
            }

            // Validate optional fields
            if (isset($item['name']) && ! is_string($item['name'])) {
                return "Item at index {$index}: 'name' must be a string.";
            }

            if (isset($item['slug']) && ! is_string($item['slug'])) {
                return "Item at index {$index}: 'slug' must be a string.";
            }

            // Validate children if exists
            if (isset($item['children'])) {
                if (! is_array($item['children'])) {
                    return "Item at index {$index}: 'children' must be an array.";
                }

                // Recursively validate children
                $childError = $this->validateStructure($item['children'], $level + 1);
                if ($childError) {
                    return $childError;
                }
            }
        }

        return null;
    }
}
