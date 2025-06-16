<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mockery;
use Src\Domain\User\DTOs\CreateUserData;
use Src\Domain\User\DTOs\UpdateUserData;
use Src\Domain\User\Repositories\UserRepositoryInterface;
use Src\Domain\User\Services\UserService;
use Tests\TestCase;

class UserDomainTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryInterface $mockRepository;
    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->userService = new UserService($this->mockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_can_create_user_data_from_request()
    {
        $requestData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'roles' => ['admin'],
            'permissions' => ['create_content']
        ];

        $userData = CreateUserData::fromRequest($requestData);

        $this->assertEquals('John Doe', $userData->name);
        $this->assertEquals('john@example.com', $userData->email);
        $this->assertEquals('password123', $userData->password);
        $this->assertEquals(['admin'], $userData->roles);
        $this->assertEquals(['create_content'], $userData->permissions);
    }

    public function test_it_can_create_update_user_data_from_request()
    {
        $requestData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'newpassword',
        ];

        $userData = UpdateUserData::fromRequest($requestData);

        $this->assertEquals('Jane Doe', $userData->name);
        $this->assertEquals('jane@example.com', $userData->email);
        $this->assertEquals('newpassword', $userData->password);
        $this->assertNull($userData->roles);
        $this->assertNull($userData->permissions);
    }

    public function test_it_can_get_all_users()
    {
        $users = collect([
            new User(['id' => 1, 'name' => 'User 1']),
            new User(['id' => 2, 'name' => 'User 2'])
        ]);

        $this->mockRepository
            ->shouldReceive('all')
            ->once()
            ->andReturn($users);

        $result = $this->userService->all();

        $this->assertEquals($users, $result);
    }

    public function test_it_can_find_user_by_id()
    {
        $user = new User(['id' => 1, 'name' => 'Test User']);

        $this->mockRepository
            ->shouldReceive('find')
            ->with(1)
            ->once()
            ->andReturn($user);

        $result = $this->userService->find(1);

        $this->assertEquals($user, $result);
    }

    public function test_it_returns_null_when_user_not_found()
    {
        $this->mockRepository
            ->shouldReceive('find')
            ->with(999)
            ->once()
            ->andReturn(null);

        $result = $this->userService->find(999);

        $this->assertNull($result);
    }

    public function test_it_can_create_user()
    {
        $userData = new CreateUserData(
            'John Doe',
            'john@example.com',
            'password123'
        );

        $user = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $this->mockRepository
            ->shouldReceive('create')
            ->with($userData)
            ->once()
            ->andReturn($user);

        $result = $this->userService->create($userData);

        $this->assertEquals($user, $result);
    }

    public function test_it_can_update_user()
    {
        $user = new User(['id' => 1, 'name' => 'Old Name']);
        $updateData = new UpdateUserData('New Name', 'new@example.com');

        $updatedUser = new User([
            'id' => 1,
            'name' => 'New Name',
            'email' => 'new@example.com'
        ]);

        $this->mockRepository
            ->shouldReceive('update')
            ->with($user, $updateData)
            ->once()
            ->andReturn($updatedUser);

        $result = $this->userService->update($user, $updateData);

        $this->assertEquals($updatedUser, $result);
    }

    public function test_it_can_delete_user()
    {
        $user = new User(['id' => 1, 'name' => 'Test User']);

        $this->mockRepository
            ->shouldReceive('delete')
            ->with($user)
            ->once()
            ->andReturn(true);

        $result = $this->userService->delete($user);

        $this->assertTrue($result);
    }

    public function test_it_can_create_token_for_user()
    {
        $user = User::factory()->create();
        $tokenName = 'test-token';

        $token = $this->userService->createToken($user, $tokenName);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function test_it_can_authenticate_user_with_valid_credentials()
    {
        $user = new User([
            'id' => 1,
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $this->mockRepository
            ->shouldReceive('findByEmail')
            ->with('test@example.com')
            ->once()
            ->andReturn($user);

        $result = $this->userService->authenticate($credentials);

        $this->assertEquals($user, $result);
    }

    public function test_it_throws_exception_for_invalid_credentials()
    {
        $user = new User([
            'id' => 1,
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        $this->mockRepository
            ->shouldReceive('findByEmail')
            ->with('test@example.com')
            ->once()
            ->andReturn($user);

        $this->expectException(ValidationException::class);
        $this->userService->authenticate($credentials);
    }

    public function test_it_throws_exception_when_user_not_found_for_authentication()
    {
        $credentials = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ];

        $this->mockRepository
            ->shouldReceive('findByEmail')
            ->with('nonexistent@example.com')
            ->once()
            ->andReturn(null);

        $this->expectException(ValidationException::class);
        $this->userService->authenticate($credentials);
    }

    public function test_it_can_update_user_profile()
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com'
        ]);

        $updateData = [
            'name' => 'New Name',
            'email' => 'new@example.com'
        ];

        $updatedUser = $this->userService->updateProfile($user, $updateData);

        $this->assertEquals('New Name', $updatedUser->name);
        $this->assertEquals('new@example.com', $updatedUser->email);
    }

    public function test_it_can_update_user_password()
    {
        $user = User::factory()->create();
        $oldPassword = $user->password;

        $updateData = [
            'password' => 'newpassword123'
        ];

        $updatedUser = $this->userService->updateProfile($user, $updateData);

        $this->assertNotEquals($oldPassword, $updatedUser->password);
        $this->assertTrue(Hash::check('newpassword123', $updatedUser->password));
    }
}