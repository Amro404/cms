<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Src\Domain\Content\Enums\ContentStatus;
use Src\Domain\Content\Enums\ContentType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->text('excerpt')->nullable();
            $table->enum('type', [ContentType::ARTICLE->value, ContentType::PAGE->value, ContentType::MEDIA->value])->default(ContentType::ARTICLE->value);
            $table->enum('status', [ContentStatus::DRAFT->value, ContentStatus::PUBLISHED->value, ContentStatus::ARCHIVED->value])->default('DRAFT');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('published_at')->nullable();
            $table->string('featured_image')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('author_id');
            $table->index(['type', 'status']);
            $table->index(['author_id', 'status']);
            $table->index(['status', 'published_at']);

            // Only create fulltext index for MySQL/MariaDB
            if (DB::getDriverName() === 'mysql') {
                $table->fullText('body');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
