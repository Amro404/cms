<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Src\Domain\User\Services\UserService;
use Src\Domain\User\DTOs\CreateUserData;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\AssignRoleRequest;
use App\Http\Requests\GivePermissionRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AuthController extends Controller
{
    use AuthorizesRequests;
    
    public function __construct(private UserService $userService) {}

    public function register(CreateUserRequest $request): JsonResponse
    {
        $data = CreateUserData::fromRequest($request->validated());

        $user = $this->userService->create($data);

        $token = $this->userService->createToken($user);

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->userService->authenticate($request->validated());
        $token = $this->userService->createToken($user);

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->userService->logout($request->user());
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->userService->updateProfile($request->user(), $request->validated());
        return response()->json($user);
    }

    /**
     * Assign a role to a user (admin only)
     */
    public function assignRole(AssignRoleRequest $request, User $user): JsonResponse
    {
        $this->authorize('assignRole', $user);
        $user = $this->userService->assignRole($user, $request->validated()['role']);
        return response()->json(['message' => 'Role assigned successfully', 'user' => $user]);
    }

    /**
     * Assign a permission to a user (admin only)
     */
    public function givePermission(GivePermissionRequest $request, User $user): JsonResponse
    {
        $this->authorize('givePermission', $user);
        $user = $this->userService->givePermission($user, $request->validated()['permission']);
        return response()->json(['message' => 'Permission granted successfully', 'user' => $user]);
    }
}
