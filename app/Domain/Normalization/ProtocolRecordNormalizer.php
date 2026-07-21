<?php

namespace App\Domain\Normalization;

final class ProtocolRecordNormalizer
{
    private array $transformations = [];

    public function normalize(array $input, array $schema): NormalizationResult
    {
        $this->transformations = [];
        $value = $this->normalizeNode($input, $schema, '');

        return new NormalizationResult(
            value: is_array($value) ? $value : $input,
            transformations: $this->transformations,
        );
    }

    private function normalizeNode(mixed $value, array $schema, string $path): mixed
    {
        $types = isset($schema['type']) ? (array) $schema['type'] : [];

        if ($value === '' && in_array('null', $types, true)) {
            return $this->changed($path, $value, null);
        }

        if (is_array($value) && ! array_is_list($value) && isset($schema['properties'])) {
            $normalized = [];
            foreach ($value as $key => $childValue) {
                $childSchema = $schema['properties'][$key] ?? [];
                $normalized[$key] = $this->normalizeNode($childValue, is_array($childSchema) ? $childSchema : [], $path.'/'.$this->escape((string) $key));
            }

            return $normalized;
        }

        if (is_array($value) && array_is_list($value) && isset($schema['items']) && is_array($schema['items'])) {
            return array_map(
                fn (mixed $item, int $index): mixed => $this->normalizeNode($item, $schema['items'], $path.'/'.$index),
                $value,
                array_keys($value),
            );
        }

        if (! is_string($value)) {
            return $value;
        }

        if (in_array('integer', $types, true) && preg_match('/^-?(0|[1-9][0-9]*)$/D', $value)) {
            $integer = filter_var($value, FILTER_VALIDATE_INT);
            if ($integer !== false) {
                return $this->changed($path, $value, $integer);
            }
        }

        if (in_array('number', $types, true) && preg_match('/^-?(0|[1-9][0-9]*)(\.[0-9]+)?([eE][+-]?[0-9]+)?$/D', $value)) {
            $number = (float) $value;
            if (is_finite($number)) {
                return $this->changed($path, $value, $number);
            }
        }

        if (in_array('boolean', $types, true) && ($value === 'true' || $value === 'false')) {
            return $this->changed($path, $value, $value === 'true');
        }

        return $value;
    }

    private function changed(string $path, mixed $from, mixed $to): mixed
    {
        $this->transformations[] = compact('path', 'from', 'to');

        return $to;
    }

    private function escape(string $part): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $part);
    }
}
