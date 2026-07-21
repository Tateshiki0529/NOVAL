<?php

namespace App\Application\Exceptions;

use RuntimeException;

final class RevisionConflict extends RuntimeException
{
    public function __construct(
        public readonly string $baseRevisionId,
        public readonly string $currentRevisionId,
    ) {
        parent::__construct('The Record has a newer Revision.');
    }
}
