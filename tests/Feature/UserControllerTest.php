<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class UserControllerTest extends FeatureTestCase
{
    use WithFaker;

    private User $user;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin'); // Assuming Spatie permission package
    }


    public function test_it_can_get_all_users()
    {
        User::factory()->count(3)->create();
        
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'avatar'
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Users retrieved successfully'
            ]);
    }


    public function test_unauthorized_user_cannot_access_users_list()
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401);
    }


    public function test_it_can_create_a_new_user()
    {
        Sanctum::actingAs($this->adminUser);

        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'avatar',
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'User created successfully',
                'data' => [
                    'name' => $userData['name'],
                    'email' => $userData['email']
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'name' => $userData['name']
        ]);
    }


    public function test_it_validates_required_fields_when_creating_user()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson('/api/v1/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }


    public function test_it_validates_email_format_when_creating_user()
    {
        Sanctum::actingAs($this->adminUser);

        $userData = [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }


    public function test_it_validates_unique_email_when_creating_user()
    {
        $existingUser = User::factory()->create();
        
        Sanctum::actingAs($this->adminUser);

        $userData = [
            'name' => 'Test User',
            'email' => $existingUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }


    public function test_it_validates_password_confirmation_when_creating_user()
    {
        Sanctum::actingAs($this->adminUser);

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }


    public function test_it_can_show_a_specific_user()
    {
        $user = User::factory()->create();
        
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson("/api/v1/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'avatar',
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'User retrieved successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);
    }


    public function test_it_returns_404_for_non_existent_user()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/v1/users/9999');

        $response->assertStatus(404);
    }


    public function test_it_can_update_a_user()
    {
        $user = User::factory()->create();
        
        Sanctum::actingAs($this->adminUser);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->putJson("/api/v1/users/{$user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ]);
    }


    public function test_it_can_update_user_password()
    {
        $user = User::factory()->create();
        $originalPassword = $user->password;
        
        Sanctum::actingAs($this->adminUser);

        $updateData = [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->putJson("/api/v1/users/{$user->id}", $updateData);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertNotEquals($originalPassword, $user->password);
    }


    public function test_it_validates_unique_email_when_updating_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Sanctum::actingAs($this->adminUser);

        $updateData = [
            'email' => $user2->email,
        ];

        $response = $this->putJson("/api/v1/users/{$user1->id}", $updateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }


    public function test_user_can_update_their_own_profile()
    {
        $user = User::factory()->create();
        
        Sanctum::actingAs($user);

        $updateData = [
            'name' => 'My Updated Name',
        ];

        $response = $this->putJson("/api/v1/users/{$user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'My Updated Name'
                ]
            ]);
    }


    public function test_it_can_delete_a_user()
    {
        $user = User::factory()->create();
        
        Sanctum::actingAs($this->adminUser);

        $response = $this->deleteJson("/api/v1/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id
        ]);
    }

    public function test_it_returns_404_when_deleting_non_existent_user()
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->deleteJson('/api/v1/users/9999');

        $response->assertStatus(404);
    }

}