<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'slug' => $this->slug,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user_id' => $this->author_id,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'featured_image' => $this->whenLoaded('featuredImage', function () {
                return [
                    'url' => $this->featuredImage->url,
                    'alt' => $this->featuredImage->alt,
                ];
            }),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'meta' => $this->whenLoaded('meta', function () {
                return $this->meta->toArray();
            }),
            'author' => new UserResource($this->whenLoaded('author')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
