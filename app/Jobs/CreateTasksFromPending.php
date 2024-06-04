<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\TaskTiming;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateTasksFromPending implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Get the date of yesterday
            $yesterday = Carbon::yesterday()->format('Y-m-d');

            // Retrieve task IDs with work_date as yesterday
            $taskIds = TaskTiming::whereDate('task_timings.work_date', $yesterday)
                ->join('tasks', function ($join) {
                    $join->on('tasks.id', '=', 'task_timings.task_id')
                        ->whereNull('tasks.deleted_at');
                })
                ->where('tasks.status', 3)
                ->pluck('task_timings.task_id')
                ->unique();

            // Create an array to store the task IDs
            $pendingTaskIds = [];
            // Create new tasks for today based on the retrieved task IDs
            foreach ($taskIds as $taskId) {
                TaskTiming::create([
                    'task_id' => $taskId,
                    'work_date' => Carbon::today()->format('Y-m-d'),
                    'description' => "Awaiting further action",
                    'type' => 0
                ]);

                // Store the created task ID in the array
                $pendingTaskIds[] = $taskId;
            }
            // Log the success message along with the created task IDs
            Log::info('Pending tasks that has been created successfully. Task IDs: ' . implode(', ', $pendingTaskIds));
        } catch (\Exception $e) {
            // Log the error message
            Log::error('Error creating new tasks: ' . $e);
        }
    }
}
