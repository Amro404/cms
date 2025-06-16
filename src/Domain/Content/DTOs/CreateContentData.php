<?php

namespace Src\Domain\Content\DTOs;

use Carbon\Carbon;
use Src\Domain\Content\Enums\ContentType;
use Src\Domain\Content\Enums\ContentStatus;
use DateTime;
use Illuminate\Http\UploadedFile;

class CreateContentData
{
    private ?Carbon $publishedAt;
    private ?string $slug;
    private ?int $authorId;
    private ?string $featuredImagePath = null;

    public function __construct(
        private string $title,
        private string $body,
        private array $categories,
        private array $tags,
        private ContentType $type = ContentType::ARTICLE,
        private ContentStatus $status = ContentStatus::DRAFT,
        private ?string $excerpt,
        private ?string $meta,
        private ?UploadedFile $featuredImage,
        private ?array $media = null,
    )
    {}

    public static function fromRequest(array $data): self
    {
        return new self(
            title: $data['title'],
            body: $data['body'],
            categories: $data['categories'],
            tags: $data['tags'],
            type: ContentType::from($data['type'] ?? ContentType::ARTICLE->value),
            status: ContentStatus::from($data['status'] ?? ContentStatus::DRAFT->value),
            excerpt: $data['excerpt'] ?? null,
            meta: $data['meta'] ?? null,
            featuredImage: $data['featured_image'] ?? null,
            media: $data['media'] ?? null,
        ); 
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getCategoires(): array
    {
        return $this->categories;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getType(): ContentType
    {
        return $this->type;
    }

    public function getStatus(): ContentStatus
    {
        return $this->status;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function getMeta(): ?string
    {
        return $this->meta;
    }

    public function getFeaturedImage(): ?UploadedFile
    {
        return $this->featuredImage;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getPublishedAt(): ?DateTime
    {
        return $this->publishedAt ?? null;
    }

    public function getAuthorId(): ?int
    {
        return $this->authorId;
    }

    public function getFeaturedImagePath(): ?string
    {
        return $this->featuredImagePath;
    }

    public function getMediaFiles(): ?array
    {
        return $this->media;
    }

    public function setPublishedAt(DateTime $publishedAt): void
    {
        if ($this->status === ContentStatus::PUBLISHED && $publishedAt) {
            $this->publishedAt = $publishedAt;
        }
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function setAuthorId(int $authorId): void
    {
        $this->authorId = $authorId;
    }

    public function setFeaturedImagePath(?string $featuredImagePath): void
    {
        $this->featuredImagePath = $featuredImagePath;
    }

}