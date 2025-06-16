<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Domain\User\Contracts\UserRepositoryInterface;
use Src\Domain\User\Repositories\UserRepository;

class UserDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }
}
