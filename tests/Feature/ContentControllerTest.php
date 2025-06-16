<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Content;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Src\Domain\Content\Enums\ContentStatus;
use Src\Domain\Content\Enums\ContentType;

class ContentControllerTest extends FeatureTestCase
{
    use WithFaker;

    private User $user;
    private User $authorUser;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->authorUser = User::factory()->create();
        $this->authorUser->assignRole('author');
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
        
        Storage::fake('public');
    }


    public function test_it_can_get_all_contents()
    {
        Content::factory()->count(3)->create([
            'status' => ContentStatus::PUBLISHED
        ]);

        Sanctum::actingAs($this->authorUser);

        $response = $this->getJson('/api/v1/contents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'slug',
                            'excerpt',
                            'status',
                            'type',
                            'published_at',
                            'created_at',
                            'updated_at',
                            'author' => [
                                'id',
                                'name',
                                'email'
                            ]
                        ]
                    ],
                    'links',
                    'meta'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Contents retrieved successfully'
            ]);
    }


    public function test_it_can_filter_contents_by_status()
    {
        Content::factory()->create(['status' => ContentStatus::PUBLISHED]);
        Content::factory()->create(['status' => ContentStatus::DRAFT]);
        Content::factory()->create(['status' => ContentStatus::ARCHIVED]);

        $response = $this->getJson('/api/v1/contents?status=published');

        $response->assertStatus(200);
        // Verify only published contents are returned
    }


    public function test_it_can_search_contents()
    {
        Content::factory()->create([
            'title' => 'Laravel Tutorial',
            'body' => 'Learn Laravel framework'
        ]);
        Content::factory()->create([
            'title' => 'PHP Basics',
            'body' => 'Learn PHP fundamentals'
        ]);

        $response = $this->getJson('/api/v1/contents?search=Laravel');

        $response->assertStatus(200);
        // Verify search results
    }


    public function test_authenticated_user_can_create_content()
    {
        $categories = Category::factory()->count(2)->create();
        $tags = Tag::factory()->count(3)->create();
        
        Sanctum::actingAs($this->authorUser);

        $contentData = [
            'title' => 'Test Article',
            'body' => 'This is a test article content.',
            'excerpt' => 'Test excerpt',
            'type' => ContentType::ARTICLE->value,
            'status' => ContentStatus::DRAFT->value,
            'categories' => $categories->pluck('id')->toArray(),
            'tags' => $tags->pluck('id')->toArray(),
            'meta' => 'Test meta description',
        ];

        $response = $this->postJson('/api/v1/contents', $contentData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'body',
                    'slug',
                    'excerpt',
                    'status',
                    'type',
                    'author_id',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Content created successfully',
                'data' => [
                    'title' => 'Test Article',
                    'body' => 'This is a test article content.',
                    'excerpt' => 'Test excerpt',
                    'status' => ContentStatus::DRAFT->value,
                    'type' => ContentType::ARTICLE->value,
                    'author_id' => $this->authorUser->id
                ]
            ]);

        $this->assertDatabaseHas('contents', [
            'title' => 'Test Article',
            'author_id' => $this->authorUser->id
        ]);
    }


    public function test_it_can_create_content_with_featured_image()
    {
        $categories = Category::factory()->count(2)->create();
        $tags = Tag::factory()->count(2)->create();
        
        Sanctum::actingAs($this->authorUser);

        $file = UploadedFile::fake()->image('featured.jpg');

        $contentData = [
            'title' => 'Article with Image',
            'body' => 'Content with featured image.',
            'excerpt' => 'Test excerpt',
            'type' => ContentType::ARTICLE->value,
            'status' => ContentStatus::DRAFT->value,
            'categories' => $categories->pluck('id')->toArray(),
            'tags' => $tags->pluck('id')->toArray(),
            'featured_image' => $file,
        ];

        $response = $this->postJson('/api/v1/contents', $contentData);

        $response->assertStatus(201);

        $content = Content::where('title', 'Article with Image')->first();
        $this->assertNotNull($content->featured_image_path);
    }


    public function test_unauthenticated_user_cannot_create_content()
    {
        $contentData = [
            'title' => 'Test Article',
            'body' => 'Test content',
            'categories' => [1],
            'tags' => [1],
        ];

        $response = $this->postJson('/api/v1/contents', $contentData);

        $response->assertStatus(401);
    }


    public function test_it_validates_required_fields_when_creating_content()
    {
        Sanctum::actingAs($this->authorUser);

        $response = $this->postJson('/api/v1/contents', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'body', 'categories', 'tags']);
    }


    public function test_it_can_show_content_by_id()
    {
        $content = Content::factory()->create([
            'status' => ContentStatus::PUBLISHED
        ]);

        $response = $this->getJson("/api/v1/contents/{$content->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Content retrieved successfully',
                'data' => [
                    'id' => $content->id,
                    'title' => $content->title,
                    'body' => $content->body,
                    'slug' => $content->slug
                ]
            ]);
    }


    public function test_it_can_show_content_by_slug()
    {
        $content = Content::factory()->create([
            'slug' => 'test-article-slug',
            'status' => ContentStatus::PUBLISHED
        ]);

        $response = $this->getJson("/api/v1/contents/test-article-slug");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Content retrieved successfully',
                'data' => [
                    'slug' => 'test-article-slug'
                ]
            ]);
    }


    public function test_it_returns_404_for_non_existent_content()
    {
        $response = $this->getJson('/api/v1/contents/9999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Content not found'
            ]);
    }


    public function test_author_can_update_their_own_content()
    {
        $content = Content::factory()->create([
            'author_id' => $this->authorUser->id,
            'title' => 'Original Title'
        ]);
        
        Sanctum::actingAs($this->authorUser);

        $updateData = [
            'title' => 'Updated Title',
            'body' => 'Updated content body',
            'excerpt' => 'Updated excerpt'
        ];

        $response = $this->putJson("/api/v1/contents/{$content->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Content updated successfully',
                'data' => [
                    'title' => 'Updated Title',
                    'body' => 'Updated content body',
                    'excerpt' => 'Updated excerpt'
                ]
            ]);

        $this->assertDatabaseHas('contents', [
            'id' => $content->id,
            'title' => 'Updated Title'
        ]);
    }


    public function test_user_cannot_update_other_users_content()
    {
        $otherUser = User::factory()->create();
        $content = Content::factory()->create([
            'author_id' => $otherUser->id
        ]);
        
        Sanctum::actingAs($this->authorUser);

        $updateData = [
            'title' => 'Unauthorized Update',
        ];

        $response = $this->putJson("/api/v1/contents/{$content->id}", $updateData);

        $response->assertStatus(403);
    }


    public function test_admin_can_update_any_content()
    {
        $content = Content::factory()->create([
            'author_id' => $this->authorUser->id,
            'title' => 'Original Title'
        ]);
        
        Sanctum::actingAs($this->adminUser);

        $updateData = [
            'title' => 'Admin Updated Title',
        ];

        $response = $this->putJson("/api/v1/contents/{$content->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Admin Updated Title'
                ]
            ]);
    }


    public function test_author_can_delete_their_own_content()
    {
        $content = Content::factory()->create([
            'author_id' => $this->authorUser->id
        ]);
        
        Sanctum::actingAs($this->authorUser);

        $response = $this->deleteJson("/api/v1/contents/{$content->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Content deleted successfully'
            ]);

        $this->assertDatabaseMissing('contents', [
            'id' => $content->id
        ]);
    }


    public function test_user_cannot_delete_other_users_content()
    {
        $otherUser = User::factory()->create();
        $content = Content::factory()->create([
            'author_id' => $otherUser->id
        ]);
        
        Sanctum::actingAs($this->authorUser);

        $response = $this->deleteJson("/api/v1/contents/{$content->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('contents', [
            'id' => $content->id
        ]);
    }


    public function test_it_can_publish_content()
    {
        $content = Content::factory()->create([
            'author_id' => $this->authorUser->id,
            'status' => ContentStatus::DRAFT
        ]);
        
        Sanctum::actingAs($this->authorUser);

        $response = $this->postJson("/api/v1/contents/{$content->id}/publish");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Content published successfully'
            ]);

        $content->refresh();
        $this->assertEquals(ContentStatus::PUBLISHED, $content->status);
        $this->assertNotNull($content->published_at);
    }


    public function test_it_can_draft_content()
    {
        $content = Content::factory()->create([
            'author_id' => $this->authorUser->id,
            'status' => ContentStatus::PUBLISHED
        ]);
        
        Sanctum::actingAs($this->authorUser);

        $response = $this->postJson("/api/v1/contents/{$content->id}/draft");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Content drafted successfully'
            ]);

        $content->refresh();
        $this->assertEquals(ContentStatus::DRAFT, $content->status);
    }


    public function test_it_can_archive_content()
    {
        $content = Content::factory()->create([
            'author_id' => $this->authorUser->id,
            'status' => ContentStatus::PUBLISHED
        ]);
        
        Sanctum::actingAs($this->adminUser);

        $response = $this->postJson("/api/v1/contents/{$content->id}/archive");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Content archived successfully'
            ]);

        $content->refresh();
        $this->assertEquals(ContentStatus::ARCHIVED, $content->status);
    }


    public function test_regular_user_cannot_archive_content()
    {
        $content = Content::factory()->create([
            'status' => ContentStatus::PUBLISHED
        ]);
        
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/contents/{$content->id}/archive");

        $response->assertStatus(403);
    }


    public function test_it_can_get_contents_by_category()
    {
        $category = Category::factory()->create(['slug' => 'technology']);
        $contents = Content::factory()->count(3)->create([
            'status' => ContentStatus::PUBLISHED
        ]);
        
        // Associate contents with category
        foreach ($contents as $content) {
            $content->categories()->attach($category->id);
        }

        $response = $this->getJson('/api/v1/contents/category/technology');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Contents by category retrieved successfully'
            ]);
    }


    public function test_it_can_get_contents_by_category_id()
    {
        $category = Category::factory()->create();
        $contents = Content::factory()->count(2)->create([
            'status' => ContentStatus::PUBLISHED
        ]);
        
        foreach ($contents as $content) {
            $content->categories()->attach($category->id);
        }

        $response = $this->getJson("/api/v1/contents/category/{$category->id}");

        $response->assertStatus(200);
    }


    public function test_it_can_get_contents_by_tag()
    {
        $tag = Tag::factory()->create(['slug' => 'laravel']);
        $contents = Content::factory()->count(3)->create([
            'status' => ContentStatus::PUBLISHED
        ]);
        
        foreach ($contents as $content) {
            $content->tags()->attach($tag->id);
        }

        $response = $this->getJson('/api/v1/contents/tag/laravel');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Contents by tag retrieved successfully'
            ]);
    }


    public function test_it_can_get_contents_by_tag_id()
    {
        $tag = Tag::factory()->create();
        $contents = Content::factory()->count(2)->create([
            'status' => ContentStatus::PUBLISHED
        ]);
        
        foreach ($contents as $content) {
            $content->tags()->attach($tag->id);
        }

        $response = $this->getJson("/api/v1/contents/tag/{$tag->id}");

        $response->assertStatus(200);
    }


    public function test_it_can_paginate_contents()
    {
        Content::factory()->count(25)->create([
            'status' => ContentStatus::PUBLISHED
        ]);

        $response = $this->getJson('/api/v1/contents?page=1&per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data',
                    'links',
                    'meta'
                ]
            ]);
    }


    public function test_it_can_filter_contents_by_author()
    {
        $author = User::factory()->create();
        Content::factory()->count(2)->create(['author_id' => $author->id]);
        Content::factory()->count(3)->create(); // Different authors

        $response = $this->getJson("/api/v1/contents?author_id={$author->id}");

        $response->assertStatus(200);
        // Verify only contents by specified author are returned
    }


    public function test_it_handles_validation_errors_gracefully()
    {
        Sanctum::actingAs($this->authorUser);

        $invalidData = [
            'title' => '', // Empty title
            'body' => '', // Empty body
            'categories' => [], // No categories
            'tags' => [], // No tags
        ];

        $response = $this->postJson('/api/v1/contents', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'body', 'categories', 'tags']);
    }
}