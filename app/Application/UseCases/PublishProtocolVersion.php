<?php

namespace App\Application\UseCases;

use App\Application\Contracts\DefinitionRepository;
use App\Application\Exceptions\InputRejected;
use App\Domain\Protocol\CanonicalJson;
use App\Domain\Protocol\ProtocolSchemaPolicy;

final readonly class PublishProtocolVersion
{
    public function __construct(
        private DefinitionRepository $definitions,
        private ProtocolSchemaPolicy $policy,
        private CanonicalJson $canonicalJson,
    ) {}

    public function execute(int $ownerId, string $versionId): array
    {
        $draft = $this->definitions->ownedDraft($versionId, $ownerId);
        $result = $this->policy->validate($draft['schema']);
        if (! $result->valid()) {
            throw new InputRejected($result);
        }

        $schemaHash = hash('sha256', $this->canonicalJson->encode($draft['schema']));
        $contentHash = hash('sha256', $this->canonicalJson->encode([
            'schema' => $draft['schema'],
            'metadata' => $draft['metadata'],
            'advisories' => $draft['advisories'],
        ]));

        return $this->definitions->publishProtocolVersion($versionId, $ownerId, [
            'schema_hash' => $schemaHash,
            'content_hash' => $contentHash,
            'hash_algorithm' => 'sha256',
            'canonicalization_version' => 'noval-json-v1',
        ]);
    }
}
