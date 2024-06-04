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

class UpdateTasksWeighted extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update_tasks_weighted:cron';

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
            // $sql = "select * from stickers where deleted_at is not null";
            // $stickers = DB::select($sql);
            // //start transaction
            // DB::beginTransaction();

            // foreach ($stickers as $sticker) {
            //     //update weight for tasks and task_timings tables
            //     Task::where('sticker_id', $sticker->id)
            //             ->update([
            //                 'weight' => null,
            //                 'priority' => null,
            //                 'sticker_id' => null
            //             ]);

            //     TaskTiming::where('sticker_id', $sticker->id)
            //             ->update([
            //                 'weight' => null,
            //                 'priority' => null,
            //                 'sticker_id' => null
            //             ]);
            // }
            // DB::commit();

            $sql = "";
            $sql .= "with recursive parent as (";

            $sql .= "select";
            $sql .= " tasks.id, tasks.deleted_at, tasks.task_parent";
            $sql .= " FROM tasks";
            $sql .= " WHERE tasks.id = 18293";

            $sql .= " union ";

            $sql .= " select";
            $sql .= " child.id, child.deleted_at, child.task_parent";
            $sql .= " FROM tasks child";
            $sql .= " JOIN parent parent ON parent.id = child.task_parent";

            $sql .= ")";

            $sql .= "select * from parent where deleted_at is not null order by id asc";

            $data = collect(DB::select($sql))->pluck('id')->toArray();

            foreach ($data as $value) {
                $this->insertRootParent($value);
            }


            // $tasks = Task::select('id', 'project_id', 'weight', 'created_at', 'updated_at')->get();

            // //start transaction
            // DB::beginTransaction();

            // foreach ($tasks as $task) {
            //     TaskProject::create([
            //         'task_id' => $task->id,
            //         'project_id' => $task->project_id,
            //         'weight' => $task->weight,
            //         'percent' => 100,
            //         'created_at' => $task->created_at ? Carbon::create($task->created_at)->format('Y/m/d H:i:s')
            //             : Carbon::now(),
            //         'updated_at' => $task->updated_at ? Carbon::create($task->updated_at)->format('Y/m/d H:i:s')
            //             : Carbon::now()
            //     ]);
            // }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
        }
    }

    private function insertRootParent($id)
    {
        $sql = "";
        $sql .= "with recursive parent as (";

        $sql .= "select";
        $sql .= " tasks.id";
        $sql .= " from tasks";
        $sql .= " where tasks.id = ".$id;

        $sql .= " union ";

        $sql .= " select";
        $sql .= " child.id";
        $sql .= " from tasks child";
        $sql .= " join parent parent on parent.id = child.task_parent";

        $sql .= ")";

        $sql .= "select * from parent order by id asc";

        //start transaction
        DB::beginTransaction();
        
        $data = collect(DB::select($sql))->pluck('id')->toArray();

        $dataString = implode(',', $data);

        $message = 'task_id: '.$id.' has been deleted with all its child: '.$dataString;
        Log::info($message);

        Task::whereIn('id', $data)->delete();

        TaskTimingProject::whereIn('task_id', $data)->delete();

        TaskTiming::whereIn('task_id', $data)->delete();

        TaskProject::where('task_id', $data)->delete();

        DB::commit();
    }
}
