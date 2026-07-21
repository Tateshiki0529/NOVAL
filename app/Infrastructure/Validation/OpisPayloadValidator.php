<?php

namespace App\Infrastructure\Validation;

use App\Domain\Validation\PayloadValidator;
use App\Domain\Validation\ValidationIssue;
use App\Domain\Validation\ValidationResult;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\JsonPointer;
use Opis\JsonSchema\Validator;

final class OpisPayloadValidator implements PayloadValidator
{
    public function validate(array $payload, array $schema): ValidationResult
    {
        $validator = new Validator;
        $validator->parser()->setOption('allowFilters', false);
        $validator->parser()->setOption('allowMappers', false);

        $dataObject = json_decode(json_encode($payload, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
        $schemaObject = json_decode(json_encode($schema, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
        $result = $validator->validate($dataObject, $schemaObject);

        if ($result->isValid()) {
            return new ValidationResult;
        }

        $formatter = new ErrorFormatter;
        $errors = $formatter->formatFlat(
            $result->error(),
            function (ValidationError $error) use ($formatter): ValidationIssue {
                $schemaPath = $error->schema()->info()->path();
                $schemaPath[] = $error->keyword();
                $rule = $error->keyword();

                return new ValidationIssue(
                    severity: 'error',
                    path: JsonPointer::pathToString($error->data()->fullPath()),
                    schemaPath: JsonPointer::pathToString($schemaPath),
                    rule: $rule,
                    code: $this->codeFor($rule),
                    messageKey: 'validation.'.$this->codeFor($rule),
                    params: $error->args(),
                    message: $formatter->formatErrorMessage($error),
                );
            },
        );

        return new ValidationResult($errors);
    }

    private function codeFor(string $rule): string
    {
        return match ($rule) {
            'required' => 'required_field_missing',
            'additionalProperties' => 'undefined_field',
            'type' => 'type_mismatch',
            'minimum', 'exclusiveMinimum' => 'value_too_small',
            'maximum', 'exclusiveMaximum' => 'value_too_large',
            'minLength' => 'string_too_short',
            'maxLength' => 'string_too_long',
            'enum' => 'enum_mismatch',
            'format' => 'format_mismatch',
            default => 'schema_'.$rule,
        };
    }
}
