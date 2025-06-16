<?php

namespace Src\Domain\Content\DTOs;

class ContentFilterData
{
    public function __construct(
        public ?int $category_id = null,
        public ?int $tag_id = null,
        public ?int $author_id = null,
        public ?string $type = null,
        public ?string $status = null,
        public ?string $search = null,
        public int $per_page = 15,
        public string $sort_by = 'published_at',
        public string $sort_direction = 'desc',
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            category_id: $request->input('category_id'),
            tag_id: $request->input('tag_id'),
            author_id: $request->input('author_id'),
            type: $request->input('type'),
            status: $request->input('status'),
            search: $request->input('search'),
            per_page: $request->input('per_page', 15),
            sort_by: $request->input('sort_by', 'published_at'),
            sort_direction: $request->input('sort_direction', 'desc'),
        );
    }

    public function toArray(): array
    {
        return [
            'category_id' => $this->category_id,
            'tag_id' => $this->tag_id,
            'author_id' => $this->author_id,
            'type' => $this->type,
            'status' => $this->status,
            'search' => $this->search,
            'sort_by' => $this->sort_by,
            'sort_direction' => $this->sort_direction,
        ];
    }
}
