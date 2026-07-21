<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('logbook_id')->constrained('logbooks')->cascadeOnDelete();
            $table->uuid('current_revision_id')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['logbook_id', 'created_at']);
        });

        Schema::create('record_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('record_id')->constrained('records')->cascadeOnDelete();
            $table->foreignUuid('parent_revision_id')->nullable()->constrained('record_revisions');
            $table->foreignUuid('protocol_version_id')->constrained('protocol_versions');
            $table->unsignedInteger('revision_number');
            $table->string('operation', 16);
            $table->dateTimeTz('occurred_at');
            $table->dateTimeTz('received_at');
            $table->json('payload');
            $table->json('source');
            $table->json('validation_warnings');
            $table->foreignId('actor_id')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['record_id', 'revision_number']);
            $table->index(['record_id', 'occurred_at']);
        });

        Schema::create('open_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->uuid('current_revision_id')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['owner_id', 'created_at']);
        });

        Schema::create('open_record_revisions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('record_id')->constrained('open_records')->cascadeOnDelete();
            $table->uuid('category_id_snapshot')->nullable();
            $table->foreignUuid('parent_revision_id')->nullable()->constrained('open_record_revisions');
            $table->unsignedInteger('revision_number');
            $table->string('operation', 16);
            $table->string('title', 200)->nullable();
            $table->longText('body');
            $table->json('tags')->nullable();
            $table->string('visibility', 16)->default('private');
            $table->dateTimeTz('occurred_at');
            $table->dateTimeTz('received_at');
            $table->json('source');
            $table->json('validation_warnings');
            $table->foreignId('actor_id')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['record_id', 'revision_number']);
            $table->index(['record_id', 'occurred_at']);
        });

        Schema::create('logbook_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('logbook_id')->constrained('logbooks')->cascadeOnDelete();
            $table->string('event_type', 32);
            $table->foreignUuid('record_revision_id')->nullable()->constrained('record_revisions');
            $table->foreignUuid('protocol_version_id')->nullable()->constrained('protocol_versions');
            $table->foreignId('actor_id')->constrained('users');
            $table->dateTimeTz('occurred_at');
            $table->timestamp('created_at')->useCurrent();
            $table->json('metadata');
            $table->index(['logbook_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logbook_events');
        Schema::dropIfExists('open_record_revisions');
        Schema::dropIfExists('open_records');
        Schema::dropIfExists('record_revisions');
        Schema::dropIfExists('records');
    }
};
