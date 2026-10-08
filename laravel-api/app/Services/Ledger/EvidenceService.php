<?php

declare(strict_types=1);

namespace App\Services\Ledger;

use App\Constants\LedgerConst;
use App\Constants\Messages;
use App\Enums\EvidenceSource;
use App\Http\Resources\Ledger\EvidenceResource;
use App\Models\Ledger\Evidence;
use App\Repositories\Ledger\EvidenceRepository;
use App\Services\AuditedCrudService;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

class EvidenceService extends AuditedCrudService
{
    protected string $resource = EvidenceResource::class;

    protected string $auditableType = 'evidence';

    public function __construct(private readonly EvidenceRepository $evidence, AuditLogger $auditLogger)
    {
        parent::__construct($evidence, $auditLogger);
    }

    /**
     * @throws ValidationException when an imported row would get a vault-owned field changed
     */
    public function update(array $payload): int
    {
        $evidence = $this->evidence->find((int) $payload['id']);
        if ($evidence->source === EvidenceSource::OBSIDIAN) {
            $this->ensureVaultFieldsUnchanged($evidence, $payload);
        }

        return parent::update($payload);
    }

    /**
     * The importer owns these fields of `source = obsidian` rows (RFC-002 §4.5); an admin edit
     * would be overwritten on the next run. Sending the current value back is fine, so a form
     * can always post every field.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws ValidationException
     */
    private function ensureVaultFieldsUnchanged(Evidence $evidence, array $payload): void
    {
        $current = [
            'title' => $evidence->title,
            'url' => $evidence->url,
            'occurred_on' => $evidence->occurred_on->format(LedgerConst::DATE_FORMAT),
            'summary' => $evidence->summary,
            'skill_ids' => $evidence->skills()->pluck('skill.id')->sort()->values()->all(),
            'tag_ids' => $evidence->tags()->pluck('tag.id')->sort()->values()->all(),
        ];

        $changed = [];
        foreach ($current as $field => $value) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }
            $sent = $payload[$field];
            if (is_array($value)) {
                $sent = array_map(intval(...), (array) $sent);
                sort($sent);
            }
            if ($sent !== $value) {
                $changed[$field] = Messages::E0024;
            }
        }

        if ($changed !== []) {
            throw ValidationException::withMessages($changed);
        }
    }
}
