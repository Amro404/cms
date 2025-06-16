<?php

namespace Src\Infrastructure\Repositories\Eloquent\User;

use App\Models\User;
use Src\Domain\User\Repositories\UserRepositoryInterface;
use Src\Domain\User\DTOs\CreateUserData;
use Src\Domain\User\DTOs\UpdateUserData;
use Illuminate\Support\Facades\Hash;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct(private User $user)
    {}

    public function all(): iterable
    {
        return $this->user->get();
    }

    public function find(int $id): ?User
    {
        return $this->user->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->user->where('email', $email)->first();
    }

    public function create(CreateUserData $data): User
    {
        $user = $this->user->newInstance([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);

        $user->save();

        if ($data->roles) $user->syncRoles($data->roles);
        if ($data->permissions) $user->syncPermissions($data->permissions);

        return $user;
    }

    public function update(User $user, UpdateUserData $data): User
    {
        if ($data->name) $user->name = $data->name;
        if ($data->email) $user->email = $data->email;
        if ($data->password) $user->password = Hash::make($data->password);
        $user->save();
        if ($data->roles) $user->syncRoles($data->roles);
        if ($data->permissions) $user->syncPermissions($data->permissions);
        return $user;
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }
}
