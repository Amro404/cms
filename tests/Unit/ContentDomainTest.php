<?php

namespace Tests\Unit;

use App\Models\Content;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Src\Domain\Content\DTOs\CreateContentData;
use Src\Domain\Content\DTOs\UpdateContentData;
use Src\Domain\Content\Enums\ContentStatus;
use Src\Domain\Content\Enums\ContentType;
use Src\Domain\Content\Repositories\ContentRepositoryInterface;
use Src\Domain\Content\Services\ContentService;
use Src\Domain\Content\Services\MediaService;
use Src\Domain\Content\Services\FileStorageService;
use Src\Domain\Content\Services\CacheService;
use Tests\TestCase;
use Src\Domain\Content\DTOs\ContentFilterData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContentDomainTest extends TestCase
{
    private ContentRepositoryInterface $mockRepository;
    private MediaService $mockMediaService;
    private FileStorageService $mockFileStorageService;
    private CacheService $mockCacheService;
    private ContentService $contentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepository = Mockery::mock(ContentRepositoryInterface::class);
        $this->mockMediaService = Mockery::mock(MediaService::class);
        $this->mockFileStorageService = Mockery::mock(FileStorageService::class);
        $this->mockCacheService = Mockery::mock(CacheService::class);
        
        $this->contentService = new ContentService(
            $this->mockRepository,
            $this->mockMediaService,
            $this->mockFileStorageService,
            $this->mockCacheService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_can_create_content_data_from_request()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('test.jpg');

        $requestData = [
            'title' => 'Test Article',
            'body' => 'This is test content',
            'categories' => [1, 2],
            'tags' => [1, 2, 3],
            'type' => ContentType::ARTICLE->value,
            'status' => ContentStatus::DRAFT->value,
            'excerpt' => 'Test excerpt',
            'meta' => 'Test meta',
            'featured_image' => $file,
            'media' => ['media1.jpg', 'media2.jpg']
        ];

        $contentData = CreateContentData::fromRequest($requestData);

        $this->assertEquals('Test Article', $contentData->getTitle());
        $this->assertEquals('This is test content', $contentData->getBody());
        $this->assertEquals([1, 2], $contentData->getCategoires()); // Note: typo in original method name
        $this->assertEquals([1, 2, 3], $contentData->getTags());
        $this->assertEquals(ContentType::ARTICLE, $contentData->getType());
        $this->assertEquals(ContentStatus::DRAFT, $contentData->getStatus());
        $this->assertEquals('Test excerpt', $contentData->getExcerpt());
        $this->assertEquals('Test meta', $contentData->getMeta());
        $this->assertInstanceOf(UploadedFile::class, $contentData->getFeaturedImage());
    }

    public function test_it_can_create_update_content_data_from_request()
    {
        $requestData = [
            'title' => 'Updated Title',
            'body' => 'Updated content',
            'status' => ContentStatus::PUBLISHED->value,
            'excerpt' => 'Updated excerpt',
            'tags' => [1, 2],
            'categories' => [1]
        ];

        $updateData = UpdateContentData::fromRequest($requestData);

        $this->assertEquals('Updated Title', $updateData->getTitle());
        $this->assertEquals('Updated content', $updateData->getBody());
        $this->assertEquals(ContentStatus::PUBLISHED, $updateData->getStatus());
        $this->assertEquals('Updated excerpt', $updateData->getExcerpt());
    }

    public function test_it_can_find_content_by_id()
    {
        $content = new Content([
            'id' => 1,
            'title' => 'Test Content',
            'body' => 'Test Body'
        ]);

        $this->mockCacheService
            ->shouldReceive('getCachedContent')
            ->with(1)
            ->once()
            ->andReturn(null);

        $this->mockRepository
            ->shouldReceive('findByIdWithRelations')
            ->with(1, ['author', 'categories', 'tags', 'media'])
            ->once()
            ->andReturn($content);

        $this->mockCacheService
            ->shouldReceive('cacheContent')
            ->with(1, Mockery::any())
            ->once();

        $result = $this->contentService->findContentById(1);

        $this->assertEquals($content, $result);
    }

    public function test_it_can_find_content_by_slug()
    {
        $content = new Content([
            'id' => 1,
            'slug' => 'test-article'
        ]);

        $this->mockCacheService
            ->shouldReceive('getCachedContent')
            ->with('test-article')
            ->once()
            ->andReturn(null);

        $this->mockRepository
            ->shouldReceive('findBySlugWithRelations')
            ->with('test-article', ['author', 'categories', 'tags', 'media'])
            ->once()
            ->andReturn($content);

        $this->mockCacheService
            ->shouldReceive('cacheContent')
            ->with('test-article', Mockery::any())
            ->once();

        $result = $this->contentService->findContentBySlug('test-article');

        $this->assertEquals($content, $result);
    }

    public function test_it_can_delete_content()
    {
        $content = new Content(['id' => 1]);

        $this->mockRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($content);

        $this->mockRepository
            ->shouldReceive('delete')
            ->with($content)
            ->once()
            ->andReturn(true);

        $this->mockCacheService
            ->shouldReceive('invalidateContentCache')
            ->once();

        $result = $this->contentService->deleteContent(1);

        $this->assertTrue($result);
    }

    public function test_it_returns_false_when_deleting_non_existent_content()
    {
        $this->mockRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $result = $this->contentService->deleteContent(999);

        $this->assertFalse($result);
    }

    public function test_it_can_publish_content()
    {
        $content = new Content(['id' => 1]);

        $this->mockRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($content);

        $this->mockRepository
            ->shouldReceive('publish')
            ->with($content)
            ->once();

        // publishContent doesn't return content, just publishes it
        $this->contentService->publishContent(1);
        
        // If no exception is thrown, the test passes
        $this->assertTrue(true);
    }

    public function test_it_can_draft_content()
    {
        $content = new Content(['id' => 1]);

        $this->mockRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($content);

        $this->mockRepository
            ->shouldReceive('draft')
            ->with($content)
            ->once();

        // draftContent returns the result of repository->draft() which is void, so it returns null
        $result = $this->contentService->draftContent(1);

        $this->assertNull($result);
    }

    public function test_it_can_archive_content()
    {
        $content = new Content(['id' => 1]);

        $this->mockRepository
            ->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($content);

        $this->mockRepository
            ->shouldReceive('archive')
            ->with($content)
            ->once();

        // archiveContent returns the result of repository->archive() which is void, so it returns null
        $result = $this->contentService->archiveContent(1);

        $this->assertNull($result);
    }

    public function test_it_can_get_contents_by_category_id()
    {
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $this->mockRepository
            ->shouldReceive('getPaginatedByCategoryId')
            ->with(1, 15, 1)
            ->once()
            ->andReturn($paginator);

        $result = $this->contentService->getContentsByCategoryId(1, 15, 1);

        $this->assertEquals($paginator, $result);
    }

    public function test_it_can_get_contents_by_category_slug()
    {
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $this->mockRepository
            ->shouldReceive('getPaginatedByCategorySlug')
            ->with('technology', 15, 1)
            ->once()
            ->andReturn($paginator);

        $result = $this->contentService->getContentsByCategorySlug('technology', 15, 1);

        $this->assertEquals($paginator, $result);
    }

    public function test_it_can_get_contents_by_tag_id()
    {
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $this->mockRepository
            ->shouldReceive('getPaginatedByTagId')
            ->with(1, 15, 1)
            ->once()
            ->andReturn($paginator);

        $result = $this->contentService->getContentsByTagId(1, 15, 1);

        $this->assertEquals($paginator, $result);
    }

    public function test_it_can_get_contents_by_tag_slug()
    {
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $this->mockRepository
            ->shouldReceive('getPaginatedByTagSlug')
            ->with('laravel', 15, 1)
            ->once()
            ->andReturn($paginator);

        $result = $this->contentService->getContentsByTagSlug('laravel', 15, 1);

        $this->assertEquals($paginator, $result);
    }

    public function test_it_throws_exception_when_content_not_found_for_publish()
    {
        $this->mockRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Content not found');

        $this->contentService->publishContent(999);
    }
}