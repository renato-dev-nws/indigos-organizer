<?php

namespace Tests\Unit;

use App\Models\Idea;
use App\Models\Task;
use App\Models\User;
use App\Notifications\IdeaOnBoardNotification;
use App\Notifications\IdeaVotedNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskDueSoonNotification;
use App\Notifications\TaskReminderNotification;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class NotificationChannelsTest extends TestCase
{
    public function test_task_notifications_use_database_and_webpush_channels(): void
    {
        $task = new Task(['title' => 'Task test']);

        $assigned = new TaskAssignedNotification($task);
        $dueSoon = new TaskDueSoonNotification($task);
        $reminder = new TaskReminderNotification($task);

        $this->assertSame(['database', WebPushChannel::class], $assigned->via(new User));
        $this->assertSame(['database', WebPushChannel::class], $dueSoon->via(new User));
        $this->assertSame(['database', WebPushChannel::class], $reminder->via(new User));
    }

    public function test_idea_notifications_use_database_and_webpush_channels(): void
    {
        $idea = new Idea(['title' => 'Idea test']);
        $voter = new User(['name' => 'Voter']);

        $onBoard = new IdeaOnBoardNotification($idea);
        $voted = new IdeaVotedNotification($idea, $voter);

        $this->assertSame(['database', WebPushChannel::class], $onBoard->via(new User));
        $this->assertSame(['database', WebPushChannel::class], $voted->via(new User));
    }
}
