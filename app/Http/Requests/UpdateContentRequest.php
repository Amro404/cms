<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Src\Domain\Content\Enums\ContentStatus;
use Src\Domain\Content\Enums\ContentType;

class UpdateContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'type' => 'required|in:' . implode(',', [
                ContentType::ARTICLE->value, 
                ContentType::PAGE->value, 
                ContentType::MEDIA->value
            ]),
            'status' => 'required|in:' . implode(',', [
                ContentStatus::DRAFT->value, 
                ContentStatus::PUBLISHED->value, 
                ContentStatus::ARCHIVED->value
            ]),
            'categories' => 'array',
            'categories.*' => 'exists:categories,id',            
            'tags' => 'array',
            'tags.*' => 'exists:tags,id',
            'featured_image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'media' => 'array',
            'media.*' => 'file|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi|max:20480',
        ];
    }
}
