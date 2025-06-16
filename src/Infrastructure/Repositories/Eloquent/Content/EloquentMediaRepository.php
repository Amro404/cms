<?php

namespace Src\Infrastructure\Repositories\Eloquent\Content;

use App\Models\Content;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Src\Domain\Content\Repositories\MediaRepositoryInterface;

class EloquentMediaRepository implements MediaRepositoryInterface
{
    public function __construct(private \App\Models\Media $model)
    {
    }

    public function store(Content $content, UploadedFile $file, string $path): Media
    {
         return $this->model->create([
            'content_id' => $content->id,
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);
    }

    public function findById(int $id): ?Media
    {
        return $this->model->find($id);
    }

    public function findByContent(Content $content): array
    {
        return $content->media()->get()->toArray();
    }

    public function update(Media $media, array $data): Media
    {
        $media->update($data);
        return $media->fresh();
    }

    public function delete(Media $media): void
    {
        $media->delete();
    }
}