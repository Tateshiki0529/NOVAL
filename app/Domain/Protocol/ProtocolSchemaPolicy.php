<?php

namespace App\Domain\Protocol;

use App\Domain\Validation\ValidationIssue;
use App\Domain\Validation\ValidationResult;

final class ProtocolSchemaPolicy
{
    private const FIELD_PATTERN = '/^[a-z][a-zA-Z0-9_]{0,63}$/D';

    private const RESERVED = [
        '__proto__', 'prototype', 'constructor', 'payload', 'source', 'id',
        'logBookId', 'protocolId', 'protocolVersion', 'occurredAt', 'receivedAt',
    ];

    private const ALLOWED_KEYWORDS = [
        '$schema', '$id', '$defs', '$ref', '$anchor', '$comment',
        'title', 'description', 'examples', 'type', 'properties', 'required',
        'additionalProperties', 'items', 'prefixItems', 'enum', 'const',
        'minimum', 'maximum', 'exclusiveMinimum', 'exclusiveMaximum',
        'minLength', 'maxLength', 'pattern', 'format', 'minItems', 'maxItems',
        'uniqueItems', 'minProperties', 'maxProperties', 'default',
        'allOf', 'anyOf', 'oneOf', 'not',
    ];

    private int $fieldCount = 0;

    /** @var list<ValidationIssue> */
    private array $errors = [];

    public function validate(array $schema): ValidationResult
    {
        $this->fieldCount = 0;
        $this->errors = [];

        if (($schema['$schema'] ?? null) !== 'https://json-schema.org/draft/2020-12/schema') {
            $this->issue('', '$schema', 'unsupported_schema_draft');
        }

        if (($schema['type'] ?? null) !== 'object') {
            $this->issue('', 'type', 'root_must_be_object');
        }

        if (($schema['additionalProperties'] ?? null) !== false) {
            $this->issue('', 'additionalProperties', 'root_must_be_strict');
        }

        $encoded = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false || strlen($encoded) > 1_048_576) {
            $this->issue('', 'maxBytes', 'schema_too_large');
        }

        $this->walk($schema, '', 1, []);

        if ($this->fieldCount > 256) {
            $this->issue('', 'maxFields', 'too_many_fields', ['maximum' => 256]);
        }

        return new ValidationResult($this->errors);
    }

    private function walk(array $schema, string $path, int $depth, array $refStack): void
    {
        if ($depth > 16) {
            $this->issue($path, 'maxDepth', 'schema_too_deep', ['maximum' => 16]);

            return;
        }

        foreach (array_keys($schema) as $keyword) {
            if (! in_array($keyword, self::ALLOWED_KEYWORDS, true)) {
                $this->issue($path.'/'.$this->escape($keyword), 'keyword', 'unsupported_schema_keyword');
            }
        }

        if (isset($schema['$ref'])) {
            $ref = $schema['$ref'];
            if (! is_string($ref) || ! str_starts_with($ref, '#/$defs/')) {
                $this->issue($path.'/$ref', '$ref', 'remote_ref_forbidden');
            } elseif (in_array($ref, $refStack, true)) {
                $this->issue($path.'/$ref', '$ref', 'cyclic_ref');
            }
        }

        $types = isset($schema['type']) ? (array) $schema['type'] : [];
        foreach ($types as $type) {
            if (! in_array($type, ['null', 'boolean', 'object', 'array', 'number', 'integer', 'string'], true)) {
                $this->issue($path.'/type', 'type', 'unsupported_type');
            }
        }

        if (isset($schema['minimum'], $schema['maximum']) && $schema['minimum'] > $schema['maximum']) {
            $this->issue($path, 'minimum', 'invalid_numeric_range');
        }
        if (isset($schema['minLength'], $schema['maxLength']) && $schema['minLength'] > $schema['maxLength']) {
            $this->issue($path, 'minLength', 'invalid_string_range');
        }
        if (isset($schema['minItems'], $schema['maxItems']) && $schema['minItems'] > $schema['maxItems']) {
            $this->issue($path, 'minItems', 'invalid_array_range');
        }

        if (isset($schema['pattern']) && @preg_match('/'.$schema['pattern'].'/u', '') === false) {
            $this->issue($path.'/pattern', 'pattern', 'invalid_pattern');
        }

        if (isset($schema['enum']) && is_array($schema['enum'])) {
            if (count($schema['enum']) > 256) {
                $this->issue($path.'/enum', 'maxEnum', 'too_many_enum_values');
            }
            $values = array_map(static fn (mixed $value): string => serialize($value), $schema['enum']);
            if (count($values) !== count(array_unique($values))) {
                $this->issue($path.'/enum', 'enum', 'duplicate_enum_value');
            }
        }

        if (isset($schema['properties']) && is_array($schema['properties'])) {
            if (in_array('object', $types, true) && ($schema['additionalProperties'] ?? null) !== false) {
                $this->issue($path, 'additionalProperties', 'object_must_be_strict');
            }
            foreach ($schema['properties'] as $fieldId => $child) {
                $this->fieldCount++;
                $fieldPath = $path.'/properties/'.$this->escape((string) $fieldId);
                if (! preg_match(self::FIELD_PATTERN, (string) $fieldId) || in_array($fieldId, self::RESERVED, true)) {
                    $this->issue($fieldPath, 'fieldId', 'invalid_field_id');
                }
                if (! is_array($child)) {
                    $this->issue($fieldPath, 'schema', 'invalid_field_schema');

                    continue;
                }
                $this->walk($child, $fieldPath, $depth + 1, $refStack);
            }
        }

        if (isset($schema['items']) && is_array($schema['items'])) {
            $this->walk($schema['items'], $path.'/items', $depth + 1, $refStack);
        }

        foreach (['allOf', 'anyOf', 'oneOf', 'prefixItems'] as $collection) {
            foreach (($schema[$collection] ?? []) as $index => $child) {
                if (is_array($child)) {
                    $this->walk($child, $path.'/'.$collection.'/'.$index, $depth + 1, $refStack);
                }
            }
        }

        if (isset($schema['not']) && is_array($schema['not'])) {
            $this->walk($schema['not'], $path.'/not', $depth + 1, $refStack);
        }

        foreach (($schema['$defs'] ?? []) as $name => $child) {
            if (is_array($child)) {
                $this->walk($child, $path.'/$defs/'.$this->escape((string) $name), $depth + 1, [...$refStack, '#/$defs/'.$name]);
            }
        }
    }

    private function issue(string $path, string $rule, string $code, array $params = []): void
    {
        $this->errors[] = new ValidationIssue(
            severity: 'error',
            path: $path,
            rule: $rule,
            code: $code,
            messageKey: 'validation.'.$code,
            params: $params,
        );
    }

    private function escape(string $part): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $part);
    }
}
