<?php
namespace Src\Domain\User\Services;

use App\Models\User;
use Src\Domain\User\Repositories\UserRepositoryInterface;
use Src\Domain\User\DTOs\CreateUserData;
use Src\Domain\User\DTOs\UpdateUserData;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(protected UserRepositoryInterface $userRepository) {}

    public function all(): iterable
    {
        return $this->userRepository->all();
    }

    public function find(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function create(CreateUserData $data): User
    {
        return $this->userRepository->create($data);
    }

    public function update(User $user, UpdateUserData $data): User
    {
        return $this->userRepository->update($user, $data);
    }

    public function delete(User $user): bool
    {
        return $this->userRepository->delete($user);
    }
    
    public function createToken(User $user, string $name = 'api'): string
    {
        return $user->createToken($name)->plainTextToken;
    }

    public function authenticate(array $credentials): User
    {
        $user =  $this->userRepository->findByEmail($credentials['email']);
        
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $user;
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function updateProfile($user, array $data): User
    {
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['email'])) {
            $user->email = $data['email'];
        }

        if (isset($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return $user;
    }

    public function assignRole(User $user, string $role): User
    {
        $user->syncRoles([$role]);
        return $user;
    }

    public function givePermission(User $user, string $permission): User
    {
        $user->givePermissionTo($permission);
        return $user;
    }
}
