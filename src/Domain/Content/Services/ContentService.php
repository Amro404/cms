<?php

namespace Src\Domain\Content\Services;

use App\Models\Content;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Src\Domain\Content\DTOs\CreateContentData;
use Src\Domain\Content\DTOs\UpdateContentData;
use Src\Domain\Content\Repositories\ContentRepositoryInterface;
use Illuminate\Support\Str;
use Src\Domain\Content\Enums\ContentStatus;
use Src\Domain\Content\Events\ContentCreated;
use Src\Domain\Content\DTOs\ContentFilterData;
use Src\Domain\Content\Events\ContentPublished;

class ContentService 
{
    public function __construct(
        private ContentRepositoryInterface $contentRepository,
        private MediaService $mediaService,
        private FileStorageService $fileStorageService,
        private CacheService $cacheService
    ) {}


    public function createContent(CreateContentData $data, User $user): Content
    {
        return DB::transaction(function () use ($data, $user) {
            $data->setAuthorId($user->id);

            $slug = $this->generateUniqueSlug($data->getTitle());
            $data->setSlug($slug);

            // Set published_at if status is published and not already set
            if ($data->getStatus() === ContentStatus::PUBLISHED) {
                $data->setPublishedAt(now());
            }

            // Handle featured image upload if provided in $data
            if ($data->getFeaturedImage() !== null) {
                $path = $this->fileStorageService->upload($data->getFeaturedImage());
                $data->setFeaturedImagePath($this->fileStorageService->url($path));
            }

            $content = $this->contentRepository->create($data);
            
            // Attach tags if provided
            if (!empty($data->getTags())) {
                $this->contentRepository->syncTags($content, $data->getTags());
            }

            // Attach categories if provided
            if (!empty($data->getCategoires())) {
                $this->contentRepository->syncCategories($content, $data->getCategoires());
            }

            // Handle additional media uploads (other than featured image)
            if ($data->getMediaFiles()) {
                foreach ($data->getMediaFiles() as $mediaFile) {
                    $this->mediaService->createMedia($content, $mediaFile);
                }
            }

            event(new ContentCreated($content));

            $this->cacheService->invalidateContentCache();

            return $this->contentRepository->findByIdWithRelations($content->id, ['author', 'categories', 'tags', 'media']);
        });
    }

    public function updateContent(int $contentId, UpdateContentData $data, User $user): Content
    {
        return DB::transaction(function () use ($contentId, $data, $user) {
            // Find the existing content
            $content = $this->contentRepository->findById($contentId);

            if (!$content) {
                throw new \Exception("Content not found");
            }

            // Authorization is handled by the controller's policy check

            // Update slug if title has changed
            if ($data->getTitle() && $data->getTitle() !== $content->title) {
                $slug = $this->generateUniqueSlug($data->getTitle());
                $data->setSlug($slug);
            }

            // Handle status changes - set published_at if changing to published
            if ($data->getStatus() === ContentStatus::PUBLISHED && 
                $content->status !== ContentStatus::PUBLISHED) {
                $data->setPublishedAt(now());
            }

            // Handle featured image update
            if ($data->getFeaturedImage() !== null) {
                // Delete old featured image if it exists
                if ($content->featured_image) {
                    $this->mediaService->deleteFeaturedImage($content);
                }

                // Upload new featured image
                $path = $this->fileStorageService->upload($data->getFeaturedImage());
                $data->setFeaturedImagePath($this->fileStorageService->url($path));
            }

            // Update the content using repository
            $this->contentRepository->update($content, $data);

            // Update tags if provided
            if (!empty($data->getTags())) {
                $this->contentRepository->syncTags($content, $data->getTags());
            }

            // Update categories if provided
            if (!empty($data->getCategories())) {
                $this->contentRepository->syncCategories($content, $data->getCategories());
            }

            // Handle media files update
            if (!empty($data->getMediaFiles())) {
                foreach ($data->getMediaFiles() as $mediaFile) {
                    $this->mediaService->createMedia($content, $mediaFile);
                }
            }

            // Handle media deletion if specified
            if ($data->getMediaToDelete()) {
                foreach ($data->getMediaToDelete() as $mediaId) {
                    $media = $this->mediaService->findById($mediaId);
                    if ($media) {
                        $this->mediaService->deleteMedia($media);
                    }
                }
            }

            // Invalidate cache
            $this->cacheService->invalidateContentCache();

            return $this->contentRepository->findByIdWithRelations($contentId, ['author', 'categories', 'tags', 'media']);
        });
    }

    public function deleteContent(int $id): bool
    {
        $content = $this->contentRepository->findById($id);

        if (! $content) {
            return false;
        }

        $result = $this->contentRepository->delete($content);

        $this->invalidateContentCache();

        return $result;
    }

    public function findContentById(int $id): ?Content
    {
        $cached = $this->cacheService->getCachedContent($id);

        if ($cached) {
            return unserialize($cached);
        }

        $content = $this->contentRepository->findByIdWithRelations($id, ['author', 'categories', 'tags', 'media']);

        if ($content) {
            $this->cacheService->cacheContent($id, serialize($content));
        }

        return $content;
    }

    public function findContentBySlug(string $slug): ?Content
    {
        $cached = $this->cacheService->getCachedContent($slug);

        if ($cached) {
            return unserialize($cached);
        }

        $content = $this->contentRepository->findBySlugWithRelations($slug, ['author', 'categories', 'tags', 'media']);

        if ($content) {
            $this->cacheService->cacheContent($slug, serialize($content));
        }

        return $content;
    }

    public function getContents(ContentFilterData $filterData): LengthAwarePaginator
    {
        $cacheKey = 'contents:' . md5(json_encode($filterData->toArray()) . ':' . $filterData->per_page);

        $cached = $this->cacheService->getCachedContentList($cacheKey);

        if ($cached) {
            return unserialize($cached);
        }

        $result = $this->contentRepository->getPaginated($filterData);

        $this->cacheService->cacheContentList($cacheKey, serialize($result));

        return $result;
    }

    private function invalidateContentCache(): void
    {
        $this->cacheService->invalidateContentCache();
    }

    public function publishContent(int $id): void
    {
        $content = $this->contentRepository->findById($id);

        if (!$content) {
            throw new \Exception("Content not found");
        }

        $this->contentRepository->publish($content);

        event(new ContentPublished($content));
    }


    public function archiveContent(int $id)
    {
        $content = $this->contentRepository->findById($id);

        if (!$content) {
            throw new \Exception("Content not found");
        }

        return $this->contentRepository->archive($content);
    }

    public function draftContent(int $id)
    {
        $content = $this->contentRepository->findById($id);

        if (!$content) {
            throw new \Exception("Content not found");
        }

        return $this->contentRepository->draft($content);
    }

    private function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        return $this->contentRepository->findBySlug($slug) !== null;
    }

    public function getContentsByTagSlug(string $tagSlug, int $perPage = 15, int $page = 1)
    {
        return $this->contentRepository->getPaginatedByTagSlug($tagSlug, $perPage, $page);
    }

    public function getContentsByCategorySlug(string $categorySlug, int $perPage = 15, int $page = 1)
    {
        return $this->contentRepository->getPaginatedByCategorySlug($categorySlug, $perPage, $page);
    }

    public function getContentsByCategoryId(int $categoryId, int $perPage = 15, int $page = 1)
    {
        return $this->contentRepository->getPaginatedByCategoryId($categoryId, $perPage, $page);
    }

    public function getContentsByTagId(int $tagId, int $perPage = 15, int $page = 1)
    {
        return $this->contentRepository->getPaginatedByTagId($tagId, $perPage, $page);
    }

}