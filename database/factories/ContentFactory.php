<?php

namespace Database\Factories;

use App\Models\Content;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Src\Domain\Content\Enums\ContentType;
use Src\Domain\Content\Enums\ContentStatus;

class ContentFactory extends Factory
{
    protected $model = Content::class;

    public function definition(): array
    {
        $title = $this->faker->unique()->sentence();
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'body' => $this->faker->paragraphs(rand(3, 7), true),
            'excerpt' => $this->faker->optional()->sentence(),
            'type' => $this->faker->randomElement([ContentType::ARTICLE->value, ContentType::PAGE->value]),
            'status' => $this->faker->randomElement([ContentStatus::DRAFT->value, ContentStatus::PUBLISHED->value, ContentStatus::ARCHIVED->value]),
            'author_id' => User::factory(),
            'published_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'featured_image' => $this->faker->optional()->imageUrl(),
            'meta' => json_encode([
                'views' => $this->faker->numberBetween(0, 1000),
            ]),
        ];
    }
}
