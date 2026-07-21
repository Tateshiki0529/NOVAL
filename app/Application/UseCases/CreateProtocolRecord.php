<?php

namespace App\Application\UseCases;

use App\Application\Contracts\DefinitionRepository;
use App\Application\Contracts\RecordRepository;
use App\Application\Exceptions\DefinitionConflict;
use App\Application\Exceptions\InputRejected;
use App\Domain\Normalization\ProtocolRecordNormalizer;
use App\Domain\Validation\PayloadValidator;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class CreateProtocolRecord
{
    public function __construct(
        private DefinitionRepository $definitions,
        private RecordRepository $records,
        private ProtocolRecordNormalizer $normalizer,
        private PayloadValidator $validator,
    ) {}

    public function execute(int $actorId, string $logbookId, array $input): array
    {
        $definition = $this->definitions->ownedLogbookDefinition($logbookId, $actorId);
        if (($input['protocolVersionId'] ?? null) !== $definition['protocol_version_id']) {
            throw new DefinitionConflict('The LogBook Protocol Version has changed.');
        }

        $payload = (array) ($input['payload'] ?? []);
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false || strlen($encoded) > 262_144) {
            throw new InvalidArgumentException('Payload is too large.');
        }

        $normalization = $this->normalizer->normalize($payload, $definition['schema']);
        $validation = $this->validator->validate($normalization->value, $definition['schema']);
        if (! $validation->valid()) {
            throw new InputRejected($validation);
        }

        $occurredAt = new DateTimeImmutable((string) ($input['occurredAt'] ?? 'now'));
        $receivedAt = new DateTimeImmutable('now');
        $source = $this->source((array) ($input['source'] ?? ['type' => 'web']));

        return $this->records->createProtocolRecord(
            $actorId,
            $definition,
            $occurredAt,
            $receivedAt,
            $normalization->value,
            $source,
            array_map(static fn ($issue): array => $issue->toArray(), $validation->warnings),
        );
    }

    private function source(array $source): array
    {
        $type = $source['type'] ?? 'web';
        if (! in_array($type, ['web', 'api', 'csv', 'webhook', 'system'], true)) {
            throw new InvalidArgumentException('Record source is invalid.');
        }

        return array_filter([
            'type' => $type,
            'integrationId' => $source['integrationId'] ?? null,
            'importId' => $source['importId'] ?? null,
            'externalId' => $source['externalId'] ?? null,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
