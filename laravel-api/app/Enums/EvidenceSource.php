<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Who owns an evidence row: typed in the admin, or synced from the Obsidian vault (RFC-002 §4.5).
 */
enum EvidenceSource: string
{
    case MANUAL = 'manual';
    case OBSIDIAN = 'obsidian';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'manual',
            self::OBSIDIAN => 'Obsidian',
        };
    }
}
