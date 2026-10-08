<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What a piece of evidence links to (REQ-002 Q3).
 */
enum EvidenceType: string
{
    case PR = 'pr';
    case ADR = 'adr';
    case INCIDENT = 'incident';
    case NOTE = 'note';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PR => 'pull request',
            self::ADR => 'decision record',
            self::INCIDENT => 'incident',
            self::NOTE => 'note',
            self::OTHER => 'other',
        };
    }
}
