<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\Content;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'content_id' => Content::factory(),
            'filename' => $this->faker->unique()->lexify('media_??????.jpg'),
            'original_name' => $this->faker->unique()->lexify('original_??????.jpg'),
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(10000, 500000),
            'path' => 'uploads/' . $this->faker->unique()->lexify('media_??????.jpg'),
            'alt_text' => $this->faker->optional()->sentence(),
        ];
    }
}
