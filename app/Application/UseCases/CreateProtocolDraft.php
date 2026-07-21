<?php

namespace App\Application\UseCases;

use App\Application\Contracts\DefinitionRepository;
use InvalidArgumentException;

final readonly class CreateProtocolDraft
{
    public function __construct(private DefinitionRepository $definitions) {}

    public function execute(int $ownerId, array $input): array
    {
        $slug = (string) ($input['slug'] ?? '');
        $version = (string) ($input['version'] ?? '');
        if (! preg_match('/^[a-z][a-z0-9-]{0,62}[a-z0-9]$/D', $slug)) {
            throw new InvalidArgumentException('Protocol slug is invalid.');
        }
        if (! preg_match('/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/D', $version)) {
            throw new InvalidArgumentException('Protocol Version must use SemVer 2.0.0.');
        }

        return $this->definitions->createProtocolDraft($ownerId, [
            'slug' => $slug,
            'version' => $version,
            'schema' => (array) ($input['schema'] ?? []),
            'metadata' => (array) ($input['metadata'] ?? ['fields' => [], 'order' => []]),
            'advisories' => (array) ($input['advisories'] ?? []),
        ]);
    }
}
