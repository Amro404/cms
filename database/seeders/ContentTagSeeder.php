<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Content;
use App\Models\Tag;

class ContentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $contentIds = Content::pluck('id')->toArray();

        $contentIds = Content::factory()->count(10)->create()->pluck('id')->toArray();
        $tagIds = Tag::factory()->count(10)->create()->pluck('id')->toArray();


        $pairs = [];
        foreach ($contentIds as $contentId) {
            $randomTags = (array)array_rand($tagIds, rand(1, min(2, count($tagIds))));
            foreach ($randomTags as $tagIndex) {
                $pairs[] = [
                    'content_id' => $contentId,
                    'tag_id' => $randomTags[$tagIndex],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        DB::table('content_tag')->insert($pairs);
    }
}
