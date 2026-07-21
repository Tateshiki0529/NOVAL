<?php

namespace App\Application\UseCases;

use App\Application\Contracts\DefinitionRepository;
use App\Application\Contracts\RecordRepository;
use App\Application\Exceptions\RevisionConflict;
use DateTimeImmutable;

final readonly class DeleteProtocolRecord
{
    public function __construct(
        private DefinitionRepository $definitions,
        private RecordRepository $records,
    ) {}

    public function execute(int $actorId, string $logbookId, string $recordId, string $baseRevisionId): array
    {
        $definition = $this->definitions->ownedRecordDefinition($logbookId, $recordId, $actorId);
        if ($definition['current_revision_id'] !== $baseRevisionId) {
            throw new RevisionConflict($baseRevisionId, $definition['current_revision_id']);
        }

        return $this->records->reviseProtocolRecord(
            $actorId,
            $definition,
            $baseRevisionId,
            'delete',
            new DateTimeImmutable('now'),
            new DateTimeImmutable('now'),
            null,
            [],
            [],
        );
    }
}
