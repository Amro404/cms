<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Domain\Content\Repositories\ContentRepositoryInterface;
use Src\Domain\Content\Repositories\MediaRepositoryInterface;
use Src\Domain\User\Repositories\UserRepositoryInterface;
use Src\Infrastructure\Repositories\Eloquent\Content\EloquentContentRepository;
use Src\Infrastructure\Repositories\Eloquent\Content\EloquentMediaRepository;
use Src\Infrastructure\Repositories\Eloquent\User\EloquentUserRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Content Domain Repositories
        $this->app->bind(ContentRepositoryInterface::class, EloquentContentRepository::class);
        $this->app->bind(MediaRepositoryInterface::class, EloquentMediaRepository::class);

        // User Domain Repositories
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);


    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
