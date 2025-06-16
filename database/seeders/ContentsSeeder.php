<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Content;

class ContentsSeeder extends Seeder
{
    public function run(): void
    {
        Content::factory()->count(20)->create();
    }
}
