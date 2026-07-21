<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Contracts\DefinitionRepository;
use App\Models\Category;
use App\Models\Logbook;
use App\Models\Protocol;
use App\Models\ProtocolRecord;
use App\Models\ProtocolVersion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class EloquentDefinitionRepository implements DefinitionRepository
{
    public function createProtocolDraft(int $ownerId, array $attributes): array
    {
        return DB::transaction(function () use ($ownerId, $attributes): array {
            $protocol = Protocol::firstOrCreate(
                ['owner_id' => $ownerId, 'slug' => $attributes['slug']],
                ['visibility' => 'private'],
            );
            $version = ProtocolVersion::create([
                'protocol_id' => $protocol->id,
                'version' => $attributes['version'],
                'state' => 'draft',
                'schema' => $attributes['schema'],
                'metadata' => $attributes['metadata'],
                'advisories' => $attributes['advisories'],
            ]);

            return $this->versionArray($version, $protocol);
        });
    }

    public function ownedDraft(string $versionId, int $ownerId): array
    {
        $version = ProtocolVersion::query()
            ->whereKey($versionId)
            ->where('state', 'draft')
            ->whereHas('protocol', fn ($query) => $query->where('owner_id', $ownerId))
            ->with('protocol')
            ->firstOrFail();

        return $this->versionArray($version, $version->protocol);
    }

    public function publishProtocolVersion(string $versionId, int $ownerId, array $hashes): array
    {
        return DB::transaction(function () use ($versionId, $ownerId, $hashes): array {
            $version = ProtocolVersion::query()
                ->whereKey($versionId)
                ->where('state', 'draft')
                ->whereHas('protocol', fn ($query) => $query->where('owner_id', $ownerId))
                ->with('protocol')
                ->lockForUpdate()
                ->firstOrFail();
            $version->fill($hashes + ['state' => 'published', 'published_at' => now()])->save();

            return $this->versionArray($version->fresh(), $version->protocol);
        });
    }

    public function createCategory(int $ownerId, array $attributes): array
    {
        return Category::create([
            'owner_id' => $ownerId,
            'name' => $attributes['name'],
            'description' => $attributes['description'],
            'visibility' => $attributes['visibility'],
            'sort_order' => $attributes['sort_order'],
        ])->toArray();
    }

    public function createLogbook(int $ownerId, array $attributes): array
    {
        $version = ProtocolVersion::query()
            ->whereKey($attributes['current_protocol_version_id'])
            ->where('state', 'published')
            ->whereHas('protocol', fn ($query) => $query->where('owner_id', $ownerId))
            ->firstOrFail();

        if ($attributes['category_id'] !== null) {
            Category::query()->whereKey($attributes['category_id'])->where('owner_id', $ownerId)->firstOrFail();
        }

        return Logbook::create([
            'owner_id' => $ownerId,
            'name' => $attributes['name'],
            'description' => $attributes['description'],
            'category_id' => $attributes['category_id'],
            'current_protocol_version_id' => $attributes['current_protocol_version_id'],
            'visibility' => $attributes['visibility'],
        ])->toArray();
    }

    public function ownedLogbookDefinition(string $logbookId, int $ownerId): array
    {
        $logbook = Logbook::query()
            ->whereKey($logbookId)
            ->where('owner_id', $ownerId)
            ->with('currentProtocolVersion')
            ->firstOrFail();
        $version = $logbook->currentProtocolVersion;
        if ($version->state !== 'published') {
            throw (new ModelNotFoundException)->setModel(ProtocolVersion::class, [$version->id]);
        }

        return [
            'logbook_id' => $logbook->id,
            'protocol_version_id' => $version->id,
            'schema' => $version->schema,
            'metadata' => $version->metadata,
        ];
    }

    public function ownedRecordDefinition(string $logbookId, string $recordId, int $ownerId): array
    {
        $record = ProtocolRecord::query()
            ->whereKey($recordId)
            ->where('logbook_id', $logbookId)
            ->whereHas('logbook', fn ($query) => $query->where('owner_id', $ownerId))
            ->with(['currentRevision'])
            ->firstOrFail();
        $version = ProtocolVersion::findOrFail($record->currentRevision->protocol_version_id);

        return [
            'record_id' => $record->id,
            'logbook_id' => $logbookId,
            'current_revision_id' => $record->current_revision_id,
            'protocol_version_id' => $version->id,
            'schema' => $version->schema,
            'metadata' => $version->metadata,
        ];
    }

    private function versionArray(ProtocolVersion $version, Protocol $protocol): array
    {
        return [
            'id' => $version->id,
            'protocol_id' => $protocol->id,
            'owner_id' => $protocol->owner_id,
            'slug' => $protocol->slug,
            'visibility' => $protocol->visibility,
            'version' => $version->version,
            'state' => $version->state,
            'schema' => $version->schema,
            'metadata' => $version->metadata,
            'advisories' => $version->advisories,
            'schema_hash' => $version->schema_hash,
            'content_hash' => $version->content_hash,
        ];
    }
}
