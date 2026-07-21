<?php

namespace App\Application\UseCases;

use App\Application\Contracts\DefinitionRepository;
use App\Application\Contracts\RecordRepository;
use App\Application\Exceptions\InputRejected;
use App\Application\Exceptions\RevisionConflict;
use App\Domain\Normalization\ProtocolRecordNormalizer;
use App\Domain\Validation\PayloadValidator;
use DateTimeImmutable;

final readonly class UpdateProtocolRecord
{
    public function __construct(
        private DefinitionRepository $definitions,
        private RecordRepository $records,
        private ProtocolRecordNormalizer $normalizer,
        private PayloadValidator $validator,
    ) {}

    public function execute(int $actorId, string $logbookId, string $recordId, array $input): array
    {
        $definition = $this->definitions->ownedRecordDefinition($logbookId, $recordId, $actorId);
        $baseRevisionId = (string) ($input['baseRevisionId'] ?? '');
        if ($definition['current_revision_id'] !== $baseRevisionId) {
            throw new RevisionConflict($baseRevisionId, $definition['current_revision_id']);
        }
        $normalization = $this->normalizer->normalize((array) ($input['payload'] ?? []), $definition['schema']);
        $validation = $this->validator->validate($normalization->value, $definition['schema']);
        if (! $validation->valid()) {
            throw new InputRejected($validation);
        }

        return $this->records->reviseProtocolRecord(
            $actorId,
            $definition,
            $baseRevisionId,
            'update',
            new DateTimeImmutable((string) ($input['occurredAt'] ?? 'now')),
            new DateTimeImmutable('now'),
            $normalization->value,
            ['type' => 'web'],
            array_map(static fn ($issue): array => $issue->toArray(), $validation->warnings),
        );
    }
}
