<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Content;
use App\Models\Category;

class ContentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $contentIds = Content::pluck('id')->toArray();
        $categoryIds = Category::pluck('id')->toArray();
        $pairs = [];
        foreach ($contentIds as $contentId) {
            // Each content gets 1-2 random categories
            $randomCategories = (array)array_rand($categoryIds, rand(1, min(2, count($categoryIds))));
            foreach ($randomCategories as $catIndex) {
                $pairs[] = [
                    'content_id' => $contentId,
                    'category_id' => $categoryIds[$catIndex],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        DB::table('content_category')->insert($pairs);
    }
}
