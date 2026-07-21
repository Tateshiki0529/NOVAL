<?php

namespace Tests\Unit\Domain;

use App\Domain\Normalization\ProtocolRecordNormalizer;
use PHPUnit\Framework\TestCase;

class ProtocolRecordNormalizerTest extends TestCase
{
    public function test_only_performs_unambiguous_conversions(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'count' => ['type' => 'integer'],
                'ratio' => ['type' => 'number'],
                'active' => ['type' => 'boolean'],
                'note' => ['type' => ['string', 'null']],
                'guess' => ['type' => 'boolean'],
            ],
        ];

        $result = (new ProtocolRecordNormalizer)->normalize([
            'count' => '12',
            'ratio' => '2.50',
            'active' => 'false',
            'note' => '',
            'guess' => 'yes',
        ], $schema);

        self::assertSame(12, $result->value['count']);
        self::assertSame(2.5, $result->value['ratio']);
        self::assertFalse($result->value['active']);
        self::assertNull($result->value['note']);
        self::assertSame('yes', $result->value['guess']);
        self::assertCount(4, $result->transformations);
    }
}
