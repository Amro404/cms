<?php
namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\API\V1\ApiController;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Src\Domain\User\DTOs\CreateUserData;
use Src\Domain\User\DTOs\UpdateUserData;
use Src\Domain\User\Services\UserService;

class UserController extends ApiController
{
    public function __construct(public UserService $userService) {}

    public function index(): JsonResponse
    {
        $users = $this->userService->all();
        return $this->collectionResponse(UserResource::collection($users), 'Users retrieved successfully');
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $data = CreateUserData::fromRequest($request->validated());
        $user = $this->userService->create($data);
        return $this->resourceResponse(new UserResource($user), 'User created successfully', 201);
    }

    public function show(User $user): JsonResponse
    {
        return $this->resourceResponse(new UserResource($user), 'User retrieved successfully');
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = UpdateUserData::fromRequest($request->validated());
        $user = $this->userService->update($user, $data);
        return $this->resourceResponse(new UserResource($user), 'User updated successfully');
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userService->delete($user);
        return $this->successResponse(null, 'User deleted successfully');
    }
}
