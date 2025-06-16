<?php

namespace Src\Domain\Content\Repositories;

use App\Models\Content;
use App\Models\Media;
use Illuminate\Http\UploadedFile;

interface MediaRepositoryInterface
{
    public function store(Content $content, UploadedFile $file, string $path): Media;
    public function findById(int $id): ?Media;
    public function findByContent(Content $content): array;
    public function update(Media $media, array $data): Media;
    public function delete(Media $media): void;
}  