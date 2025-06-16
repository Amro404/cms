<?php

namespace Src\Domain\Content\Repositories;

use App\Models\Content;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Src\Domain\Content\DTOs\CreateContentData;
use Src\Domain\Content\DTOs\UpdateContentData;
use App\Http\DTOs\ContentFilterData;

interface ContentRepositoryInterface
{
    public function create(CreateContentData $data): Content;
    public function update(Content $content, UpdateContentData $data): void;
    public function delete(Content $content): bool;
    public function findById(int $id): ?Content;
    public function findByIdWithRelations(int $id, array $relations = []): ?Content;
    public function findBySlugWithRelations(string $slug, array $relations = []): ?Content;
    public function findBySlug(string $slug): ?Content;
    public function getPaginated(ContentFilterData $filterData): LengthAwarePaginator;
    public function getPublished(ContentFilterData $filterData): LengthAwarePaginator;
    public function publish(Content $content): void;
    public function draft(Content $content): void;
    public function archive(Content $content): void;
    public function syncTags(Content $content, array $tagIds): void;
    public function syncCategories(Content $content, array $tagIds): void;
    public function getPaginatedByTagSlug(string $tagSlug, int $perPage = 15, int $page = 1);
    public function getPaginatedByCategorySlug(string $categorySlug, int $perPage = 15, int $page = 1);
    public function getPaginatedByCategoryId(int $categoryId, int $perPage = 15, int $page = 1);
    public function getPaginatedByTagId(int $tagId, int $perPage = 15, int $page = 1);
}