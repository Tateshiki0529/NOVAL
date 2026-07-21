<?php

namespace App\Application\Exceptions;

use App\Domain\Validation\ValidationResult;
use RuntimeException;

final class InputRejected extends RuntimeException
{
    public function __construct(public readonly ValidationResult $result)
    {
        parent::__construct('Input failed validation.');
    }
}
