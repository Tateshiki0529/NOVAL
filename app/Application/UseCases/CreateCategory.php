<?php

namespace App\Application\UseCases;

use App\Application\Contracts\DefinitionRepository;
use InvalidArgumentException;

final readonly class CreateCategory
{
    public function __construct(private DefinitionRepository $definitions) {}

    public function execute(int $ownerId, array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120) {
            throw new InvalidArgumentException('Category name is invalid.');
        }

        return $this->definitions->createCategory($ownerId, [
            'name' => $name,
            'description' => ($input['description'] ?? null) ?: null,
            'visibility' => 'private',
            'sort_order' => (int) ($input['sortOrder'] ?? 0),
        ]);
    }
}
