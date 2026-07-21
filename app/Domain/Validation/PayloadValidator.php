<?php

namespace App\Domain\Validation;

interface PayloadValidator
{
    public function validate(array $payload, array $schema): ValidationResult;
}
