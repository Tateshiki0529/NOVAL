<?php

namespace App\Application\UseCases;

use App\Application\Contracts\DefinitionRepository;
use InvalidArgumentException;

final readonly class CreateLogbook
{
    public function __construct(private DefinitionRepository $definitions) {}

    public function execute(int $ownerId, array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120) {
            throw new InvalidArgumentException('LogBook name is invalid.');
        }

        return $this->definitions->createLogbook($ownerId, [
            'name' => $name,
            'description' => ($input['description'] ?? null) ?: null,
            'category_id' => ($input['categoryId'] ?? null) ?: null,
            'current_protocol_version_id' => (string) ($input['protocolVersionId'] ?? ''),
            'visibility' => 'private',
        ]);
    }
}
