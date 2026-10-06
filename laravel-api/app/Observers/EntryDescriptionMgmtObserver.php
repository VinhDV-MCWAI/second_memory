<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Management\EntryDescriptionMgmt;
use App\Models\Management\EntryMgmt;
use Illuminate\Support\Facades\Log;

class EntryDescriptionMgmtObserver
{
    /**
     * Handle the EntryDescriptionMgmt "deleted" event.
     * Clean up all parent entries' layout_structure when a description is deleted.
     */
    public function deleted(EntryDescriptionMgmt $entryDescriptionMgmt): void
    {
        try {
            // Find all entries that contain this description in their layout_structure
            $entries = EntryMgmt::whereNotNull('layout_structure')
                ->where('is_delete', false)
                ->get();

            foreach ($entries as $entry) {
                $layoutStructure = $entry->layout_structure;

                if (is_array($layoutStructure) && ! empty($layoutStructure)) {
                    $updated = $this->removeDescriptionFromStructure($layoutStructure, $entryDescriptionMgmt->id);

                    if ($updated) {
                        $entry->layout_structure = $layoutStructure;
                        $entry->save();

                        Log::info("Cleaned entry_desc_id {$entryDescriptionMgmt->id} from entry {$entry->id} layout_structure");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to clean layout_structure after description deletion: '.$e->getMessage());
        }
    }

    /**
     * Handle the EntryDescriptionMgmt "updated" event.
     * Soft delete handling - when is_delete is set to true.
     */
    public function updated(EntryDescriptionMgmt $entryDescriptionMgmt): void
    {
        // If description is soft deleted, clean up parent references
        if ($entryDescriptionMgmt->is_delete === true && $entryDescriptionMgmt->isDirty('is_delete')) {
            $this->deleted($entryDescriptionMgmt);
        }
    }

    /**
     * Recursively remove description from layout structure
     *
     * @return bool True if structure was modified
     */
    private function removeDescriptionFromStructure(array &$structure, int $descriptionId): bool
    {
        $modified = false;

        foreach ($structure as $key => $item) {
            // Remove if this item matches the description
            if (isset($item['entry_desc_id']) && $item['entry_desc_id'] === $descriptionId) {
                unset($structure[$key]);
                $modified = true;

                continue;
            }

            // Recursively check children
            if (isset($item['children']) && is_array($item['children'])) {
                if ($this->removeDescriptionFromStructure($structure[$key]['children'], $descriptionId)) {
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
