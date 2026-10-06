<?php

namespace App\Observers;

use App\Models\Management\CategoryMgmt;
use App\Models\Management\EntryMgmt;
use Illuminate\Support\Facades\Log;

class EntryMgmtObserver
{
    /**
     * Handle the EntryMgmt "deleted" event.
     * Clean up all parent categories' layout_structure when an entry is deleted.
     */
    public function deleted(EntryMgmt $entryMgmt): void
    {
        try {
            // Find all categories that contain this entry in their layout_structure
            $categories = CategoryMgmt::whereNotNull('layout_structure')
                ->where('is_delete', false)
                ->get();

            foreach ($categories as $category) {
                $layoutStructure = $category->layout_structure;

                if (is_array($layoutStructure) && ! empty($layoutStructure)) {
                    $updated = $this->removeEntryFromStructure($layoutStructure, $entryMgmt->id);

                    if ($updated) {
                        $category->layout_structure = $layoutStructure;
                        $category->save();

                        Log::info("Cleaned entry_mgmt_id {$entryMgmt->id} from category {$category->id} layout_structure");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to clean layout_structure after entry deletion: '.$e->getMessage());
        }
    }

    /**
     * Handle the EntryMgmt "updated" event.
     * Soft delete handling - when is_delete is set to true.
     */
    public function updated(EntryMgmt $entryMgmt): void
    {
        // If entry is soft deleted, clean up parent references
        if ($entryMgmt->is_delete === true && $entryMgmt->isDirty('is_delete')) {
            $this->deleted($entryMgmt);
        }
    }

    /**
     * Recursively remove entry from layout structure
     *
     * @return bool True if structure was modified
     */
    private function removeEntryFromStructure(array &$structure, int $entryId): bool
    {
        $modified = false;

        foreach ($structure as $key => $item) {
            // Remove if this item matches the entry
            if (isset($item['entry_mgmt_id']) && $item['entry_mgmt_id'] === $entryId) {
                unset($structure[$key]);
                $modified = true;

                continue;
            }

            // Recursively check children
            if (isset($item['children']) && is_array($item['children'])) {
                if ($this->removeEntryFromStructure($structure[$key]['children'], $entryId)) {
                    $modified = true;
                }
            }
        }

        // Re-index array to avoid gaps
        if ($modified) {
            $structure = array_values($structure);
        }

        return $modified;
    }
}
