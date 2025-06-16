<?php

namespace Src\Domain\Content\Services;

use App\Models\Content;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Src\Domain\Content\Repositories\MediaRepositoryInterface;


class MediaService
{
    public function __construct(
        private MediaRepositoryInterface $mediaRepository,
        private FileStorageService $fileStorageService
    ) {}

    public function createMedia(Content $content, UploadedFile $file, ?string $type = null): Media
    {
        $path = $this->fileStorageService->upload($file, $type);
        
        try {
    
            return $this->mediaRepository->store($content, $file, $path);
        } catch (\Exception $e) {
            $this->fileStorageService->delete($path);
            throw $e;
        }
    }

    public function uploadFeaturedImage(UploadedFile $file, ?string $type = null): string
    {
        $path = $this->fileStorageService->upload($file, $type);

        if (!$path) {
            throw new \RuntimeException('Failed to upload the featured image.');
        }

        return $path;
    }

    public function deleteFeaturedImage(Content $content): void
    {
        if ($content->path) {
            $this->fileStorageService->delete($content->path);
        }
    }

    public function getFeaturedImageUrl(Content $content): string
    {
        return $this->fileStorageService->url($content->featured_image);
    }

    public function deleteMedia(Media $media): void
    {
        // Delete from database
        $this->mediaRepository->delete($media);
        
        // Delete file from storage
        if ($media->path) {
            $this->fileStorageService->delete($media->path);
        }
    }

    public function getMediaUrl(Media $media): string
    {
        return $this->fileStorageService->url($media->path);
    }

    public function getMediaPath(Media $media): string
    {
        return $this->fileStorageService->path($media->path);
    }

    public function findById(int $id): ?Media
    {
        return $this->mediaRepository->findById($id);
    }

    public function findByContent(Content $content): array
    {
        return $this->mediaRepository->findByContent($content);
    }

    public function updateMedia(Media $media, array $data): Media
    {
        return $this->mediaRepository->update($media, $data);
    }
}
