<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class AuthControllerTest extends FeatureTestCase
{
    use WithFaker;


    public function test_it_can_register_a_new_user()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ],
                'token'
            ])
            ->assertJson([
                'user' => [
                    'name' => $userData['name'],
                    'email' => $userData['email']
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'name' => $userData['name']
        ]);

        $this->assertIsString($response->json('token'));
        $this->assertNotEmpty($response->json('token'));
    }


    public function test_registration_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }


    public function test_registration_validates_email_format()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'invalid-email-format',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }


    public function test_registration_validates_unique_email()
    {
        $existingUser = User::factory()->create();

        $userData = [
            'name' => 'Test User',
            'email' => $existingUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }


    public function test_registration_validates_password_confirmation()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }


    public function test_registration_validates_minimum_password_length()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }


    public function test_it_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/login', $credentials);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ],
                'token'
            ])
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);

        $this->assertIsString($response->json('token'));
        $this->assertNotEmpty($response->json('token'));
    }


    public function test_it_cannot_login_with_invalid_email()
    {
        $credentials = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/login', $credentials);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }


    public function test_it_cannot_login_with_invalid_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/v1/auth/login', $credentials);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }


    public function test_login_validates_required_fields()
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }


    public function test_login_validates_email_format()
    {
        $credentials = [
            'email' => 'invalid-email',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/login', $credentials);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }


    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully'
            ]);

        // Verify token is deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class
        ]);
    }


    public function test_unauthenticated_user_cannot_logout()
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }


    public function test_authenticated_user_can_get_their_profile()
    {
        $user = User::factory()->create();
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]);
    }


    public function test_unauthenticated_user_cannot_get_profile()
    {
        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(401);
    }


    public function test_authenticated_user_can_update_their_profile()
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com'
        ]);
        
        Sanctum::actingAs($user);

        $updateData = [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ];

        $response = $this->putJson('/api/v1/auth/profile', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'name' => 'New Name',
                'email' => 'new@example.com'
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com'
        ]);
    }


    public function test_user_can_update_their_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword')
        ]);
        
        Sanctum::actingAs($user);

        $updateData = [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->putJson('/api/v1/auth/profile', $updateData);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }


    public function test_profile_update_validates_unique_email()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Sanctum::actingAs($user1);

        $updateData = [
            'email' => $user2->email,
        ];

        $response = $this->putJson('/api/v1/auth/profile', $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }


    public function test_profile_update_validates_password_confirmation()
    {
        $user = User::factory()->create();
        
        Sanctum::actingAs($user);

        $updateData = [
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ];

        $response = $this->putJson('/api/v1/auth/profile', $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }


    public function test_admin_can_assign_role_to_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $user = User::factory()->create();
        
        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/auth/users/{$user->id}/assign-role", [
            'role' => 'editor'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Role assigned successfully'
            ]);

        $this->assertTrue($user->fresh()->hasRole('editor'));
    }


    public function test_regular_user_cannot_assign_roles()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Sanctum::actingAs($user1);

        $response = $this->postJson("/api/v1/auth/users/{$user2->id}/assign-role", [
            'role' => 'editor'
        ]);

        $response->assertStatus(403);
    }


    public function test_admin_can_give_permission_to_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $user = User::factory()->create();
        
        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/auth/users/{$user->id}/give-permission", [
            'permission' => 'create content'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Permission granted successfully'
            ]);

        $this->assertTrue($user->fresh()->hasPermissionTo('create content'));
    }


    public function test_regular_user_cannot_give_permissions()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Sanctum::actingAs($user1);

        $response = $this->postJson("/api/v1/auth/users/{$user2->id}/give-permission", [
            'permission' => 'create content'
        ]);

        $response->assertStatus(403);
    }


    public function test_it_validates_role_assignment_data()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $user = User::factory()->create();
        
        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/auth/users/{$user->id}/assign-role", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }


    public function test_it_validates_permission_assignment_data()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $user = User::factory()->create();
        
        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/auth/users/{$user->id}/give-permission", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['permission']);
    }


    public function test_authentication_creates_token_with_proper_abilities()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/v1/auth/login', $credentials);

        $token = $response->json('token');
        
        // Verify token is valid by making authenticated request
        $profileResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/user');

        $profileResponse->assertStatus(200);
    }

}
