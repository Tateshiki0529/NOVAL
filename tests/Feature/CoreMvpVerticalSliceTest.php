<?php

namespace Tests\Feature;

use App\Application\Exceptions\RevisionConflict;
use App\Application\UseCases\CreateCategory;
use App\Application\UseCases\CreateLogbook;
use App\Application\UseCases\CreateOpenRecord;
use App\Application\UseCases\CreateProtocolDraft;
use App\Application\UseCases\CreateProtocolRecord;
use App\Application\UseCases\DeleteOpenRecord;
use App\Application\UseCases\DeleteProtocolRecord;
use App\Application\UseCases\PublishProtocolVersion;
use App\Application\UseCases\UpdateOpenRecord;
use App\Application\UseCases\UpdateProtocolRecord;
use App\Models\ProtocolVersion;
use App\Models\RecordRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class CoreMvpVerticalSliceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_publish_a_protocol_and_store_both_record_types(): void
    {
        $user = User::factory()->create();
        $draft = app(CreateProtocolDraft::class)->execute($user->id, [
            'slug' => 'fuel-log',
            'version' => '1.0.0',
            'schema' => $this->schema(),
            'metadata' => [
                'order' => ['/mileage', '/fullTank'],
                'fields' => [
                    '/mileage' => ['kind' => 'integer', 'label' => '走行距離'],
                    '/fullTank' => ['kind' => 'boolean', 'label' => '満タン'],
                ],
            ],
        ]);
        $published = app(PublishProtocolVersion::class)->execute($user->id, $draft['id']);
        $category = app(CreateCategory::class)->execute($user->id, ['name' => 'プレオ']);
        $logbook = app(CreateLogbook::class)->execute($user->id, [
            'name' => '給油記録',
            'categoryId' => $category['id'],
            'protocolVersionId' => $published['id'],
        ]);
        $record = app(CreateProtocolRecord::class)->execute($user->id, $logbook['id'], [
            'protocolVersionId' => $published['id'],
            'occurredAt' => '2026-07-21T10:30:00+09:00',
            'payload' => ['mileage' => '142381', 'fullTank' => 'true'],
            'source' => ['type' => 'web'],
        ]);
        $openRecord = app(CreateOpenRecord::class)->execute($user->id, [
            'title' => '実装開始',
            'body' => 'Core MVPの最初のVertical Sliceを保存する。',
            'categoryId' => $category['id'],
            'tags' => ['noval', 'mvp'],
            'occurredAt' => '2026-07-21T11:00:00+09:00',
        ]);

        self::assertSame('published', $published['state']);
        self::assertSame(142381, $record['payload']['mileage']);
        self::assertTrue($record['payload']['fullTank']);
        self::assertSame('private', $openRecord['visibility']);

        $updated = app(UpdateProtocolRecord::class)->execute($user->id, $logbook['id'], $record['id'], [
            'baseRevisionId' => $record['revisionId'],
            'occurredAt' => '2026-07-21T10:35:00+09:00',
            'payload' => ['mileage' => 142400, 'fullTank' => false],
        ]);
        self::assertSame(2, $updated['revisionNumber']);
        self::assertSame($record['revisionId'], RecordRevision::findOrFail($updated['revisionId'])->parent_revision_id);

        try {
            app(UpdateProtocolRecord::class)->execute($user->id, $logbook['id'], $record['id'], [
                'baseRevisionId' => $record['revisionId'],
                'payload' => ['mileage' => 1],
            ]);
            self::fail('A stale base Revision must conflict.');
        } catch (RevisionConflict $conflict) {
            self::assertSame($updated['revisionId'], $conflict->currentRevisionId);
        }

        $deleted = app(DeleteProtocolRecord::class)->execute($user->id, $logbook['id'], $record['id'], $updated['revisionId']);
        self::assertSame('delete', $deleted['operation']);
        self::assertSame($updated['payload'], $deleted['payload']);

        $updatedOpen = app(UpdateOpenRecord::class)->execute($user->id, $openRecord['id'], [
            'baseRevisionId' => $openRecord['revisionId'],
            'title' => '実装進行中',
            'body' => 'Revisionを追加した。',
            'tags' => ['noval'],
        ]);
        $deletedOpen = app(DeleteOpenRecord::class)->execute($user->id, $openRecord['id'], $updatedOpen['revisionId']);
        self::assertSame('delete', $deletedOpen['operation']);
        self::assertSame('Revisionを追加した。', $deletedOpen['body']);

        $this->assertDatabaseCount('record_revisions', 3);
        $this->assertDatabaseCount('logbook_events', 3);
        $this->assertDatabaseCount('open_record_revisions', 3);
    }

    public function test_published_protocol_version_is_immutable(): void
    {
        $user = User::factory()->create();
        $draft = app(CreateProtocolDraft::class)->execute($user->id, [
            'slug' => 'immutable-test',
            'version' => '1.0.0',
            'schema' => $this->schema(),
        ]);
        app(PublishProtocolVersion::class)->execute($user->id, $draft['id']);
        $version = ProtocolVersion::findOrFail($draft['id']);

        $this->expectException(LogicException::class);
        $version->update(['schema' => ['type' => 'string']]);
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
