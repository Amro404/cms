<?php

namespace Src\Infrastructure\Repositories\Eloquent\Content;

use Src\Domain\Content\Repositories\ContentRepositoryInterface;
use App\Models\Content;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Src\Domain\Content\DTOs\ContentFilterData;
use Src\Domain\Content\DTOs\CreateContentData;
use Src\Domain\Content\DTOs\UpdateContentData;
use Src\Domain\Content\Enums\ContentStatus;

class EloquentContentRepository implements ContentRepositoryInterface
{

    public function __construct(private Content $model)
    {}

    public function create(CreateContentData $data): Content
    {
        return $this->model->create([
            'title' => $data->getTitle(),
            'slug' => $data->getSlug(),
            'body' => $data->getBody(),
            'excerpt' => $data->getExcerpt(),
            'type' =>  $data->getType(),
            'status' => $data->getStatus(),
            'author_id' => $data->getAuthorId(),
            'published_at' => $data->getPublishedAt(),
            'featured_image' => $data->getFeaturedImagePath(),
            'meta' => json_encode([
                'views' => $data->getMeta()
            ]),
        ]);
    }

    public function update(Content $content, UpdateContentData $data): void
    {
        $this->model::where('id', $content->id)->update([
            'title' => $data->getTitle(),
            'slug' => $data->getSlug() ?? $content->slug,
            'body' => $data->getBody(),
            'excerpt' => $data->getExcerpt(),
            'status' => $data->getStatus(),
            'published_at' => $data->getPublishedAt(),
            'featured_image' => $data->getFeaturedImagePath(),
            'meta' => json_encode([
                'views' => $data->getMeta()
            ]),
        ]);
    }

    public function delete(Content $content): bool
    {
        return $content->delete();
    }

    public function findById(int $id): ?Content
    {
        return $this->model::find($id);
    }

    public function findByIdWithRelations(int $id, array $relations = []): ?Content
    {
        return $this->model::with($relations)->find($id);
    }

    public function findBySlugWithRelations(string $slug, array $relations = []): ?Content
    {
        return $this->model::with($relations)->where('slug', $slug)->first();
    }

    public function findBySlug(string $slug): ?Content
    {
        return $this->model::where('slug', $slug)->first();
    }

    public function getPaginated(ContentFilterData $filterData): LengthAwarePaginator
    {
        $query = $this->model::query();
        $filters = $filterData->toArray();
        $perPage = $filterData->per_page;

        
        if (!empty($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('categories.id', $filters['category_id']);
            });
        }

        if (!empty($filters['tag_id'])) {
            $query->whereHas('tags', function ($q) use ($filters) {
                $q->where('tags.id', $filters['tag_id']);
            });
        }

        if (!empty($filters['author_id'])) {
            $query->where('author_id', $filters['author_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        // Search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereRaw('MATCH(body) AGAINST (? IN BOOLEAN MODE)', [$search])
                  ->orWhere('title', 'like', "%$search%")
                  ->orWhere('slug', 'like', "%$search%");
            });
        }
        // Sorting
        $sortBy = $filters['sort_by'] ?? 'published_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // Eager load relations for API resource
        $query->with(['author', 'categories', 'tags', 'media']);

        return $query->paginate($perPage);
    }

    public function getPublished(ContentFilterData $filterData): LengthAwarePaginator
    {
        $filters = $filterData->toArray();
        $perPage = $filterData->per_page;
        return $this->model::where(array_merge($filters, ['is_published' => true]))->paginate($perPage);
    }    

    public function publish(Content $content): void
    {
        $content->update([
            'status' => ContentStatus::PUBLISHED->value,
            'published_at' => now(),
        ]);
    }

    public function draft(Content $content): void
    {
        $content->update([
            'status' => ContentStatus::DRAFT->value,
        ]);
    }

    public function archive(Content $content): void
    {
        $content->update([
            'status' => ContentStatus::ARCHIVED->value,
        ]);
    }

    public function syncTags(Content $content, array $tagIds): void
    {
        $content->tags()->sync($tagIds);
    }

    public function syncCategories(Content $content, array $categoryIds): void
    {
        $content->categories()->sync($categoryIds);
    }

    public function getPaginatedByTagSlug(string $tagSlug, int $perPage = 15, int $page = 1)
    {
        return $this->model::whereHas('tags', function ($q) use ($tagSlug) {
            $q->where('slug', $tagSlug);
        })
        ->with(['author', 'categories', 'tags', 'media'])
        ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getPaginatedByCategorySlug(string $categorySlug, int $perPage = 15, int $page = 1)
    {
        return $this->model::whereHas('categories', function ($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        })
        ->with(['author', 'categories', 'tags', 'media'])
        ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getPaginatedByCategoryId(int $categoryId, int $perPage = 15, int $page = 1)
    {
        return $this->model::whereHas('categories', function ($q) use ($categoryId) {
            $q->where('id', $categoryId);
        })
        ->with(['author', 'categories', 'tags', 'media'])
        ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getPaginatedByTagId(int $tagId, int $perPage = 15, int $page = 1)
    {
        return $this->model::whereHas('tags', function ($q) use ($tagId) {
            $q->where('id', $tagId);
        })
        ->with(['author', 'categories', 'tags', 'media'])
        ->paginate($perPage, ['*'], 'page', $page);
    }
}