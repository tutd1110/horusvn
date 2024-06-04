<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Task;
use App\Models\TaskProject;
use App\Models\TaskTiming;
use App\Models\TaskTimingProject;
use App\Models\Sticker;
use App\Models\Priority;
use Exception;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UpdateTasksOutDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update_tasks_out_date:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            
            $tasks = DB::table('tasks')
                ->select('tasks.id', 'tasks.deadline', 'tasks.status', 'task_timings.work_date')
                ->join('task_timings', function ($join) {
                    $join->on('tasks.id', '=', 'task_timings.task_id')
                        ->whereNull('task_timings.deleted_at')
                        ->whereNull('task_timings.task_assignment_id');
                })
                ->orderBy('tasks.id', 'asc')
                ->get();

            foreach ($tasks as $key => $task) {
                if ($task->deadline != null && Carbon::parse($task->deadline)->lt(Carbon::parse($task->work_date))) {
                    DB::table('tasks')
                        ->where('id', $task->id)
                        ->update(['status' => 0]);
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
        }
    }

}
