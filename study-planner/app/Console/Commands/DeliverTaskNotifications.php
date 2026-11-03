<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Task;
use App\Services\TaskNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeliverTaskNotifications extends Command
{
    protected $signature = 'reminders:tasks {--backfill : Schedule existing future tasks before delivery}';

    protected $description = 'Mark eligible persisted task notifications delivered in-app.';

    public function handle(): int
    {
        if ($this->option('backfill')) {
            Task::where('deadline', '>', now())->chunkById(100, function ($tasks) {
                foreach ($tasks as $task) {
                    DB::transaction(function () use ($task) {
                        $locked = Task::whereKey($task->id)->lockForUpdate()->first();
                        if ($locked) {
                            app(TaskNotificationService::class)->synchronize($locked);
                        }
                    });
                }
            });
        }
        $count = Notification::whereNotNull('task_id')->whereNull('cancelled_at')
            ->where('delivery_status', 'pending')->where('scheduled_at', '<=', now())
            ->update(['delivery_status' => 'sent', 'sent_at' => now()]);
        $this->info("Delivered {$count} task notifications.");

        return self::SUCCESS;
    }
}
