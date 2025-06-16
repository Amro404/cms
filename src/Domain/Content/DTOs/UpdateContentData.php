<?php

namespace Src\Domain\Content\DTOs;

use App\Models\Content;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Src\Domain\Content\Enums\ContentStatus;

class UpdateContentData
{ 
    private ?string $featuredImagePath = null;

    public function __construct(
        private ?string $title,
        private ?string $body,
        private ?string $excerpt = null,
        private ContentStatus $status = ContentStatus::DRAFT,
        private ?string $slug = null,
        private ?UploadedFile $featuredImage = null,
        private ?array $tags = null,
        private ?array $categories = null,
        private ?array $mediaFiles = null,
        private ?array $mediaToDelete = null,
        private ?Carbon $publishedAt = null,
        private ?array $meta = null,
    ) 
    {}

    public static function fromRequest(array $data = []): self
    {
        return new self(
            title: $data['title'],
            body: $data['body'],
            excerpt: $data['excerpt'] ?? null,
            status: ContentStatus::from($data['status'] ?? ContentStatus::DRAFT->value),
            slug: $data['slug'] ?? null,
            featuredImage: $data['featured_image'] ?? null,
            tags: $data['tags'] ?? null,
            categories: $data['categories'] ?? null,
            mediaFiles: $data['media'] ?? null,
            mediaToDelete: $data['media_to_delete'] ?? null,
            publishedAt: isset($data['published_at']) ? Carbon::parse($data['published_at']) : null,
            meta: $data['meta'] ?? null,

        );
    }

    // Getters
    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function getStatus(): ?ContentStatus
    {
        return $this->status;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getFeaturedImage(): ?UploadedFile
    {
        return $this->featuredImage;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function getCategories(): ?array
    {
        return $this->categories;
    }

    public function getMediaFiles(): ?array
    {
        return $this->mediaFiles;
    }

    public function getMediaToDelete(): ?array
    {
        return $this->mediaToDelete;
    }

    public function getPublishedAt(): ?Carbon
    {
        return $this->publishedAt;
    }

    public function getFeaturedImagePath(): ?string
    {
        return $this->featuredImagePath;
    }

    public function getMeta(): ?string
    {
        return $this->meta;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function setFeaturedImagePath(string $path): void
    {
        $this->featuredImagePath = $path;
    }

    public function setPublishedAt(Carbon $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }
 

}