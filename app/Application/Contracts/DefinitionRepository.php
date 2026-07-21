<?php

namespace App\Application\Contracts;

interface DefinitionRepository
{
    public function createProtocolDraft(int $ownerId, array $attributes): array;

    public function ownedDraft(string $versionId, int $ownerId): array;

    public function publishProtocolVersion(string $versionId, int $ownerId, array $hashes): array;

    public function createCategory(int $ownerId, array $attributes): array;

    public function createLogbook(int $ownerId, array $attributes): array;

    public function ownedLogbookDefinition(string $logbookId, int $ownerId): array;

    public function ownedRecordDefinition(string $logbookId, string $recordId, int $ownerId): array;
}
