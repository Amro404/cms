<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagsSeeder extends Seeder
{
    public function run(): void
    {
        Tag::factory()->count(15)->create();
    }
}
