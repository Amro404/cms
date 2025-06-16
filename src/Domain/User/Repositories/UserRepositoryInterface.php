<?php
namespace Src\Domain\User\Repositories;

use App\Models\User;
use Src\Domain\User\DTOs\CreateUserData;
use Src\Domain\User\DTOs\UpdateUserData;

interface UserRepositoryInterface
{
    public function all(): iterable;
    public function find(int $id): ?User;
    public function create(CreateUserData $data): User;
    public function update(User $user, UpdateUserData $data): User;
    public function delete(User $user): bool;
    public function findByEmail(string $email): ?User;
}
