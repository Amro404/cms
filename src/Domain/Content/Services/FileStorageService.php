<?php
namespace Src\Domain\Content\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileStorageService
{
    protected array $allowedTypes = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'video' => ['mp4', 'mov', 'avi', 'webm'],
        'audio' => ['mp3', 'wav', 'ogg'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],
    ];

    protected array $maxSizes = [
        'image' => 5 * 1024 * 1024, // 5MB
        'video' => 100 * 1024 * 1024, // 100MB
        'audio' => 20 * 1024 * 1024, // 20MB
        'document' => 10 * 1024 * 1024, // 10MB
    ];

    public function upload(UploadedFile $file, ?string $type = null): string
    {
        $type = $type ?: $this->detectType($file);
        $this->validate($file, $type);
        
        $folder = $this->getFolderForType($type);
        $filename = $this->uniqueFilename($file);
        $path = $file->storeAs($folder, $filename, 'public');

        if (!$path) {
            throw new \RuntimeException('Failed to store file.');
        }

        return $path;
    }

    public function delete(string $path): void
    {
        Storage::disk('public')->delete($path);
    }

    public function url(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    public function path(string $path): string
    {
        return Storage::disk('public')->path($path);
    }

    public function validate(UploadedFile $file, string $type): void
    {
        $ext = strtolower($file->getClientOriginalExtension());
        
        if (!isset($this->allowedTypes[$type]) || !in_array($ext, $this->allowedTypes[$type])) {
            throw ValidationException::withMessages([
                'file' => 'Invalid file type for ' . $type . '. Allowed: ' . implode(', ', $this->allowedTypes[$type] ?? [])
            ]);
        }

        if ($file->getSize() > ($this->maxSizes[$type] ?? 0)) {
            throw ValidationException::withMessages([
                'file' => 'File size exceeds maximum allowed for ' . $type . ' (' . ($this->maxSizes[$type] / 1024 / 1024) . 'MB)'
            ]);
        }
    }

    public function detectType(UploadedFile $file): string
    {
        $mime = $file->getMimeType();
        
        if (Str::startsWith($mime, 'image/')) return 'image';
        if (Str::startsWith($mime, 'video/')) return 'video';
        if (Str::startsWith($mime, 'audio/')) return 'audio';
        if (Str::contains($mime, ['pdf', 'msword', 'officedocument', 'excel', 'powerpoint'])) return 'document';

        throw ValidationException::withMessages(['file' => 'Unsupported media type: ' . $mime]);
    }

    public function getFolderForType(string $type): string
    {
        return match($type) {
            'image' => 'contents/images',
            'video' => 'contents/videos',
            'audio' => 'contents/audio',
            'document' => 'contents/documents',
            default => 'contents/other',
        };
    }

    public function uniqueFilename(UploadedFile $file): string
    {
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext = $file->getClientOriginalExtension();
        return Str::slug($name) . '-' . uniqid() . '.' . $ext;
    }
}

