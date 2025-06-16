<?php

namespace Src\Domain\Content\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Src\Domain\Content\Events\ContentPublished;

class SendContentPublishedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of seconds before the job can be retried.
     */
    public $retryAfter = 60;
    /**
     * Handle the event.
     *
     * @param ContentPublished $event
     * @return void
     */
    public function handle(ContentPublished $event): void
    {
          Log::info("Content published: {$event->content->title}", [
            'content_id' => $event->content->id,
            'author_id' => $event->content->author_id
        ]);
    }
}