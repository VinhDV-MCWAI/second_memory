<?php

declare(strict_types=1);

namespace App\Services\Ledger;

use App\Constants\LedgerConst;
use App\Enums\AuditEvent;
use App\Enums\EvidenceSource;
use App\Enums\EvidenceType;
use App\Models\Ledger\Evidence;
use App\Models\Ledger\Skill;
use App\Models\Ledger\Tag;
use App\Repositories\Ledger\EvidenceImportRepository;
use App\Services\AuditLogger;
use Illuminate\Support\Carbon;

/**
 * Syncs the complete list of published Obsidian notes into `note` evidence (RFC-002 §4.5, REQ-002 US-4):
 * upsert by `external_key`, hide imported rows missing from the list, never write an unchanged row.
 * The importer owns title, url, date, summary, skills and tags of its rows; `is_public` stays the
 * owner's choice except that a new note starts public and a removed one is hidden.
 *
 * @phpstan-type Note array{external_key: string, title: string, url: string, occurred_on: string, summary?: string|null, tags?: list<string>, skills?: list<string>}
 * @phpstan-type Report array{created: int, updated: int, unchanged: int, hidden: int, unknown_skills: list<string>}
 */
final class EvidenceImportService
{
    private const AUDITABLE_EVIDENCE = 'evidence';

    private const AUDITABLE_TAG = 'tag';

    public function __construct(
        private readonly EvidenceImportRepository $imports,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{notes: list<Note>, dry_run?: bool}  $payload
     * @return Report
     */
    public function import(array $payload): array
    {
        $notes = $payload['notes'];
        $dryRun = (bool) ($payload['dry_run'] ?? false);

        $skills = $this->byLowerName($this->imports->skillsByName($this->lowerNames($notes, 'skills'))->all());
        $tagNames = $this->spellings($notes, 'tags');
        $tags = $this->byLowerName($this->imports->tagsByName(array_keys($tagNames))->all());
        $rows = $this->imports->importedRows();
        $now = Carbon::now();

        $report = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'hidden' => 0, 'unknown_skills' => []];
        $unknown = [];
        $writes = [];
        foreach ($notes as $note) {
            $wanted = $this->wanted($note, $skills, $unknown);
            $row = $rows->get($note['external_key']);
            if ($row === null) {
                $report['created']++;
                $writes[] = [$note, $wanted, null];
            } elseif ($row->unpublished_at !== null || $this->current($row) !== $wanted) {
                $report['updated']++;
                $writes[] = [$note, $wanted, $row];
            } else {
                $report['unchanged']++;
            }
        }

        $listed = array_flip(array_column($notes, 'external_key'));
        $removed = $rows->filter(fn (Evidence $row, string $key): bool => ! isset($listed[$key]) && $row->unpublished_at === null);
        $report['hidden'] = $removed->count();
        $report['unknown_skills'] = array_values($unknown);

        if ($dryRun) {
            return $report;
        }

        foreach ($writes as [$note, $wanted, $row]) {
            $tagIds = $this->tagIds($wanted['tags'], $tagNames, $tags);
            $skillIds = array_map(fn (string $name): int => $skills[$name]->id, $wanted['skills']);
            $row === null ? $this->create($note, $wanted, $skillIds, $tagIds) : $this->update($row, $note, $wanted, $skillIds, $tagIds);
        }
        foreach ($removed as $row) {
            $wasPublic = $row->is_public;
            $this->imports->hide($row, $now);
            $this->audit($row->id, AuditEvent::UPDATED, ['is_public' => $wasPublic, 'unpublished_at' => null], [
                'is_public' => false, 'unpublished_at' => $now->toIso8601String(),
            ]);
        }

        return $report;
    }

    /**
     * The fields the importer owns, in the form they are compared: skills and tags as sorted lower-case names.
     *
     * @param  Note  $note
     * @param  array<string, Skill>  $skills
     * @param  array<string, string>  $unknown  lower-case => first spelling, filled here
     * @return array{title: string, url: string, occurred_on: string, summary: string|null, skills: list<string>, tags: list<string>}
     */
    private function wanted(array $note, array $skills, array &$unknown): array
    {
        $skillNames = [];
        foreach ($note['skills'] ?? [] as $name) {
            $lower = mb_strtolower($name);
            if (isset($skills[$lower])) {
                $skillNames[] = $lower;
            } else {
                $unknown[$lower] ??= $name;
            }
        }

        return [
            'title' => $note['title'],
            'url' => $note['url'],
            'occurred_on' => $note['occurred_on'],
            'summary' => $note['summary'] ?? null,
            'skills' => $this->sortedUnique($skillNames),
            'tags' => $this->sortedUnique(array_map(mb_strtolower(...), $note['tags'] ?? [])),
        ];
    }

    /**
     * @return array{title: string, url: string, occurred_on: string, summary: string|null, skills: list<string>, tags: list<string>}
     */
    private function current(Evidence $row): array
    {
        return [
            'title' => $row->title,
            'url' => $row->url,
            'occurred_on' => $row->occurred_on->format(LedgerConst::DATE_FORMAT),
            'summary' => $row->summary,
            'skills' => $this->sortedUnique($row->skills->map(fn (Skill $skill): string => mb_strtolower($skill->name))->all()),
            'tags' => $this->sortedUnique($row->tags->map(fn (Tag $tag): string => mb_strtolower($tag->name))->all()),
        ];
    }

    /**
     * @param  Note  $note
     * @param  array<string, mixed>  $wanted
     * @param  list<int>  $skillIds
     * @param  list<int>  $tagIds
     */
    private function create(array $note, array $wanted, array $skillIds, array $tagIds): void
    {
        $evidence = $this->imports->create([
            ...$this->attributes($wanted),
            'type' => EvidenceType::NOTE,
            'source' => EvidenceSource::OBSIDIAN,
            'external_key' => $note['external_key'],
            'is_public' => true,
        ], $skillIds, $tagIds);

        $this->audit($evidence->id, AuditEvent::CREATED, null, [
            ...$wanted,
            'type' => EvidenceType::NOTE->value,
            'source' => EvidenceSource::OBSIDIAN->value,
            'external_key' => $note['external_key'],
            'is_public' => true,
        ]);
    }

    /**
     * A republished note only clears `unpublished_at`: it stays private until the owner makes it public again.
     *
     * @param  Note  $note
     * @param  array<string, mixed>  $wanted
     * @param  list<int>  $skillIds
     * @param  list<int>  $tagIds
     */
    private function update(Evidence $row, array $note, array $wanted, array $skillIds, array $tagIds): void
    {
        $before = [...$this->current($row), 'unpublished_at' => $row->unpublished_at?->toIso8601String()];
        $this->imports->update($row, [...$this->attributes($wanted), 'unpublished_at' => null], $skillIds, $tagIds);
        $this->audit($row->id, AuditEvent::UPDATED, $before, [...$wanted, 'unpublished_at' => null]);
    }

    /**
     * @param  array<string, mixed>  $wanted
     * @return array<string, mixed>
     */
    private function attributes(array $wanted): array
    {
        return [
            'title' => $wanted['title'],
            'url' => $wanted['url'],
            'occurred_on' => $wanted['occurred_on'],
            'summary' => $wanted['summary'],
        ];
    }

    /**
     * Ids of the wanted tags; a tag that does not exist yet is created with the note's spelling.
     *
     * @param  list<string>  $wanted  lower-case
     * @param  array<string, string>  $spellings  lower-case => first spelling
     * @param  array<string, Tag>  $tags  lower-case => tag, filled here
     * @return list<int>
     */
    private function tagIds(array $wanted, array $spellings, array &$tags): array
    {
        $ids = [];
        foreach ($wanted as $name) {
            if (! isset($tags[$name])) {
                $tags[$name] = $this->imports->createTag($spellings[$name]);
                $this->audit($tags[$name]->id, AuditEvent::CREATED, null, ['name' => $tags[$name]->name], self::AUDITABLE_TAG);
            }
            $ids[] = $tags[$name]->id;
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>  $new
     */
    private function audit(int $id, AuditEvent $event, ?array $old, array $new, string $type = self::AUDITABLE_EVIDENCE): void
    {
        // `via` marks the row as written by the importer; the actor is the token's admin
        $this->auditLogger->record($type, $id, $event, $old, [...$new, 'via' => LedgerConst::IMPORT_AUDIT_VIA]);
    }

    /**
     * @param  list<Note>  $notes
     * @return list<string>
     */
    private function lowerNames(array $notes, string $field): array
    {
        return array_keys($this->spellings($notes, $field));
    }

    /**
     * @param  list<Note>  $notes
     * @return array<string, string> lower-case => first spelling
     */
    private function spellings(array $notes, string $field): array
    {
        $names = [];
        foreach ($notes as $note) {
            foreach ($note[$field] ?? [] as $name) {
                $names[mb_strtolower($name)] ??= $name;
            }
        }

        return $names;
    }

    /**
     * @template T of Skill|Tag
     *
     * @param  list<T>  $models
     * @return array<string, T>
     */
    private function byLowerName(array $models): array
    {
        $byName = [];
        foreach ($models as $model) {
            $byName[mb_strtolower($model->name)] = $model;
        }

        return $byName;
    }

    /**
     * @param  array<int, string>  $names
     * @return list<string>
     */
    private function sortedUnique(array $names): array
    {
        $names = array_values(array_unique($names));
        sort($names);

        return $names;
    }
}
