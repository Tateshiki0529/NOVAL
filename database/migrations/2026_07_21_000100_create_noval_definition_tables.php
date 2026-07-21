<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protocols', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('slug', 64);
            $table->string('visibility', 16)->default('private');
            $table->timestamps();
            $table->unique(['owner_id', 'slug']);
        });

        Schema::create('protocol_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('protocol_id')->constrained('protocols')->cascadeOnDelete();
            $table->string('version', 32);
            $table->string('state', 16)->default('draft');
            $table->json('schema');
            $table->json('metadata');
            $table->json('advisories');
            $table->char('schema_hash', 64)->nullable()->index();
            $table->char('content_hash', 64)->nullable()->index();
            $table->string('hash_algorithm', 16)->nullable();
            $table->string('canonicalization_version', 32)->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('published_at')->nullable();
            $table->unique(['protocol_id', 'version']);
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('visibility', 16)->default('private');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index(['owner_id', 'sort_order']);
        });

        Schema::create('logbooks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('visibility', 16)->default('private');
            $table->foreignUuid('current_protocol_version_id')->constrained('protocol_versions');
            $table->timestamps();
            $table->index(['owner_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logbooks');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('protocol_versions');
        Schema::dropIfExists('protocols');
    }
};
