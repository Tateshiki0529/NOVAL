<?php

namespace Tests\Unit\Domain;

use App\Domain\Protocol\ProtocolSchemaPolicy;
use PHPUnit\Framework\TestCase;

class ProtocolSchemaPolicyTest extends TestCase
{
    public function test_accepts_a_strict_core_schema(): void
    {
        $result = (new ProtocolSchemaPolicy)->validate($this->schema());

        self::assertTrue($result->valid());
        self::assertSame([], $result->errors);
    }

    public function test_collects_multiple_unsafe_schema_errors(): void
    {
        $schema = $this->schema();
        $schema['additionalProperties'] = true;
        $schema['properties']['__proto__'] = ['$ref' => 'https://example.com/schema.json'];
        $schema['properties']['mileage']['minimum'] = 100;
        $schema['properties']['mileage']['maximum'] = 1;

        $result = (new ProtocolSchemaPolicy)->validate($schema);
        $codes = array_map(static fn ($issue) => $issue->code, $result->errors);

        self::assertFalse($result->valid());
        self::assertContains('root_must_be_strict', $codes);
        self::assertContains('invalid_field_id', $codes);
        self::assertContains('remote_ref_forbidden', $codes);
        self::assertContains('invalid_numeric_range', $codes);
    }

    private function schema(): array
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'properties' => [
                'mileage' => ['type' => 'integer', 'minimum' => 0],
                'fullTank' => ['type' => 'boolean'],
            ],
            'required' => ['mileage'],
            'additionalProperties' => false,
        ];
    }
}
