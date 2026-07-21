<?php

namespace App\Providers;

use App\Application\Contracts\DefinitionRepository;
use App\Application\Contracts\RecordRepository;
use App\Domain\Validation\PayloadValidator;
use App\Infrastructure\Persistence\Repositories\EloquentDefinitionRepository;
use App\Infrastructure\Persistence\Repositories\EloquentRecordRepository;
use App\Infrastructure\Validation\OpisPayloadValidator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DefinitionRepository::class, EloquentDefinitionRepository::class);
        $this->app->bind(RecordRepository::class, EloquentRecordRepository::class);
        $this->app->bind(PayloadValidator::class, OpisPayloadValidator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
