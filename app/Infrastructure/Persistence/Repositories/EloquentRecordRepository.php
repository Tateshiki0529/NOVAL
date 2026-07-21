<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Contracts\RecordRepository;
use App\Application\Exceptions\RevisionConflict;
use App\Models\Category;
use App\Models\LogbookEvent;
use App\Models\OpenRecord;
use App\Models\OpenRecordRevision;
use App\Models\ProtocolRecord;
use App\Models\RecordRevision;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentRecordRepository implements RecordRepository
{
    public function createProtocolRecord(
        int $actorId,
        array $definition,
        DateTimeImmutable $occurredAt,
        DateTimeImmutable $receivedAt,
        array $payload,
        array $source,
        array $warnings,
    ): array {
        return DB::connection()->transaction(function () use ($actorId, $definition, $occurredAt, $receivedAt, $payload, $source, $warnings): array {
            $record = ProtocolRecord::create(['logbook_id' => $definition['logbook_id']]);
            $revision = RecordRevision::create([
                'record_id' => $record->id,
                'revision_number' => 1,
                'parent_revision_id' => null,
                'protocol_version_id' => $definition['protocol_version_id'],
                'operation' => 'create',
                'occurred_at' => $occurredAt,
                'received_at' => $receivedAt,
                'payload' => $payload,
                'source' => $source,
                'validation_warnings' => $warnings,
                'actor_id' => $actorId,
            ]);
            $record->update(['current_revision_id' => $revision->id]);
            LogbookEvent::create([
                'logbook_id' => $definition['logbook_id'],
                'event_type' => 'record.created',
                'record_revision_id' => $revision->id,
                'protocol_version_id' => $definition['protocol_version_id'],
                'actor_id' => $actorId,
                'occurred_at' => $occurredAt,
                'metadata' => [],
            ]);

            return [
                'id' => $record->id,
                'revisionId' => $revision->id,
                'revisionNumber' => 1,
                'protocolVersionId' => $revision->protocol_version_id,
                'occurredAt' => $revision->occurred_at->format(DATE_RFC3339_EXTENDED),
                'receivedAt' => $revision->received_at->format(DATE_RFC3339_EXTENDED),
                'payload' => $revision->payload,
            ];
        });
    }

    public function createOpenRecord(
        int $ownerId,
        DateTimeImmutable $occurredAt,
        DateTimeImmutable $receivedAt,
        array $input,
    ): array {
        return DB::connection()->transaction(function () use ($ownerId, $occurredAt, $receivedAt, $input): array {
            if ($input['category_id'] !== null) {
                Category::query()->whereKey($input['category_id'])->where('owner_id', $ownerId)->firstOrFail();
            }
            $record = OpenRecord::create([
                'owner_id' => $ownerId,
                'category_id' => $input['category_id'],
            ]);
            $revision = OpenRecordRevision::create([
                'record_id' => $record->id,
                'category_id_snapshot' => $input['category_id'],
                'revision_number' => 1,
                'parent_revision_id' => null,
                'operation' => 'create',
                'title' => $input['title'],
                'body' => $input['body'],
                'tags' => $input['tags'],
                'visibility' => 'private',
                'occurred_at' => $occurredAt,
                'received_at' => $receivedAt,
                'source' => $input['source'],
                'validation_warnings' => $input['validation_warnings'],
                'actor_id' => $ownerId,
            ]);
            $record->update(['current_revision_id' => $revision->id]);

            return [
                'id' => $record->id,
                'revisionId' => $revision->id,
                'revisionNumber' => 1,
                'title' => $revision->title,
                'body' => $revision->body,
                'tags' => $revision->tags,
                'visibility' => $revision->visibility,
                'occurredAt' => $revision->occurred_at->format(DATE_RFC3339_EXTENDED),
                'receivedAt' => $revision->received_at->format(DATE_RFC3339_EXTENDED),
            ];
        });
    }

    public function reviseProtocolRecord(
        int $actorId,
        array $definition,
        string $baseRevisionId,
        string $operation,
        DateTimeImmutable $occurredAt,
        DateTimeImmutable $receivedAt,
        ?array $payload,
        array $source,
        array $warnings,
    ): array {
        return DB::connection()->transaction(function () use ($actorId, $definition, $baseRevisionId, $operation, $occurredAt, $receivedAt, $payload, $source, $warnings): array {
            $record = ProtocolRecord::query()->whereKey($definition['record_id'])->lockForUpdate()->firstOrFail();
            if ($record->current_revision_id !== $baseRevisionId) {
                throw new RevisionConflict($baseRevisionId, $record->current_revision_id);
            }
            $current = RecordRevision::findOrFail($record->current_revision_id);
            $deleting = $operation === 'delete';
            $revision = RecordRevision::create([
                'record_id' => $record->id,
                'revision_number' => $current->revision_number + 1,
                'parent_revision_id' => $current->id,
                'protocol_version_id' => $current->protocol_version_id,
                'operation' => $operation,
                'occurred_at' => $deleting ? $current->occurred_at : $occurredAt,
                'received_at' => $receivedAt,
                'payload' => $deleting ? $current->payload : $payload,
                'source' => $deleting ? $current->source : $source,
                'validation_warnings' => $deleting ? $current->validation_warnings : $warnings,
                'actor_id' => $actorId,
            ]);
            $record->update(['current_revision_id' => $revision->id]);
            LogbookEvent::create([
                'logbook_id' => $record->logbook_id,
                'event_type' => 'record.'.$operation.'d',
                'record_revision_id' => $revision->id,
                'protocol_version_id' => $revision->protocol_version_id,
                'actor_id' => $actorId,
                'occurred_at' => $receivedAt,
                'metadata' => [],
            ]);

            return [
                'id' => $record->id,
                'revisionId' => $revision->id,
                'revisionNumber' => $revision->revision_number,
                'operation' => $operation,
                'protocolVersionId' => $revision->protocol_version_id,
                'occurredAt' => $revision->occurred_at->format(DATE_RFC3339_EXTENDED),
                'receivedAt' => $revision->received_at->format(DATE_RFC3339_EXTENDED),
                'payload' => $revision->payload,
            ];
        });
    }

    public function reviseOpenRecord(
        int $ownerId,
        string $recordId,
        string $baseRevisionId,
        string $operation,
        DateTimeImmutable $occurredAt,
        DateTimeImmutable $receivedAt,
        array $input,
    ): array {
        return DB::connection()->transaction(function () use ($ownerId, $recordId, $baseRevisionId, $operation, $occurredAt, $receivedAt, $input): array {
            $record = OpenRecord::query()->whereKey($recordId)->where('owner_id', $ownerId)->lockForUpdate()->firstOrFail();
            if ($record->current_revision_id !== $baseRevisionId) {
                throw new RevisionConflict($baseRevisionId, $record->current_revision_id);
            }
            $current = OpenRecordRevision::findOrFail($record->current_revision_id);
            $deleting = $operation === 'delete';
            $categoryId = $deleting ? $record->category_id : $input['category_id'];
            if ($categoryId !== null) {
                Category::query()->whereKey($categoryId)->where('owner_id', $ownerId)->firstOrFail();
            }
            $revision = OpenRecordRevision::create([
                'record_id' => $record->id,
                'category_id_snapshot' => $categoryId,
                'revision_number' => $current->revision_number + 1,
                'parent_revision_id' => $current->id,
                'operation' => $operation,
                'title' => $deleting ? $current->title : $input['title'],
                'body' => $deleting ? $current->body : $input['body'],
                'tags' => $deleting ? $current->tags : $input['tags'],
                'visibility' => 'private',
                'occurred_at' => $deleting ? $current->occurred_at : $occurredAt,
                'received_at' => $receivedAt,
                'source' => $deleting ? $current->source : $input['source'],
                'validation_warnings' => $deleting ? $current->validation_warnings : [],
                'actor_id' => $ownerId,
            ]);
            $record->update(['category_id' => $categoryId, 'current_revision_id' => $revision->id]);

            return [
                'id' => $record->id,
                'revisionId' => $revision->id,
                'revisionNumber' => $revision->revision_number,
                'operation' => $operation,
                'title' => $revision->title,
                'body' => $revision->body,
                'tags' => $revision->tags,
                'visibility' => $revision->visibility,
            ];
        });
    }
}
