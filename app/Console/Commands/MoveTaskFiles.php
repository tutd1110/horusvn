<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Task;
use App\Models\TaskDeadline;
use App\Models\TaskFile;
use App\Models\QuestionReview;
use App\Models\EmployeeReviewPoint;
use App\Models\EmployeeAnswer;
use App\Models\UserPersonalInfo;
use App\Models\UserJobDetail;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use File;

class MoveTaskFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'move_task_files:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move all tasks images from description column in tasks table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
        //     $tasks = Task::where('description', 'LIKE', '%<img src=%')->get();

            $model = new Task();
            $connectionName = $model->getConnectionName();
            //start transaction control
            DB::connection($connectionName)->beginTransaction();

            $task = Task::whereNotNull('deadline')->get();

            foreach ($tasks as $task) {
                TaskDeadline::create([
                    'task_id' => $task->id,
                    'estimate_date' => $task->deadline,
                    'user_id' => $task->user_id,
                    'status' => $task->status
                ]);
            }

            DB::connection($connectionName)->commit();

            //start transaction
            // DB::beginTransaction();

        //     foreach ($tasks as $task) {
        //         $description = $task->description;

        //         $dom = $dom = new \DOMDocument();
        //         $dom->loadHTML($description);

        //         $imageTags = $dom->getElementsByTagName('img');
        //         foreach ($imageTags as $img) {
        //             $src = $img->getAttribute('src');
        //             if (strpos($src, 'data:image/') === 0) {
        //                 // Image data URI found, extract the data and save as a file
        //                 $imageData = explode(',', $src)[1]; // get the base64-encoded image data
        //                 // get the image extension from the MIME type
        //                 $imageExtension = explode('/', explode(':', substr($src, 0, strpos($src, ';')))[1])[1];
        //                 $imageFilename = uniqid() . '.' . $imageExtension;// generate a unique filename
                        
        //                 //file path
        //                 $path = public_path('task_files/'.$task->id.'/'.$imageFilename);

        //                 //create file folder
        //                 File::ensureDirectoryExists(dirname(public_path('task_files/'.$task->id)));
        //                 if (!is_dir(public_path('task_files/'.$task->id))) {
        //                     File::makeDirectory(public_path('task_files/'.$task->id));
        //                 }

        //                 $taskFile = TaskFile::create([
        //                     'task_id' => $task->id,
        //                     'path' => 'task_files/'.$task->id.'/'.$imageFilename
        //                 ]);

        //                 if ($taskFile) {
        //                     File::put($path, base64_decode($imageData));

        //                     // remove image string from description
        //                     $description = preg_replace('/<img[^>]+>/', '', $description);
        //                 }
        //             }
        //         }

        //         // update task description
        //         $task->description = $description;
        //         $task->save();

        //         Log::info($task->id);
        //     }

            //insert employee infomation
            // $employees = User::get();

            // foreach ($employees as $employee) {
            //     UserPersonalInfo::create([
            //         'user_id' => $employee->id,
            //         'fullname' => $employee->fullname,
            //         'birthday' => $employee->birthday,
            //         'phone' => $employee->phone,
            //     ]);

            //     UserJobDetail::create([
            //         'user_id' => $employee->id,
            //         'position' => $employee->position,
            //         'department_id' => $employee->department_id,
            //         'start_date' => Carbon::create($employee->created_at)->format('Y-m-d'),
            //         'official_start_date' => Carbon::create($employee->date_official)->format('Y-m-d')
            //     ]);
            // }

            // $points = EmployeeReviewPoint::whereIn('review_id', [8,20,39,40,41])->get();
            // foreach ($points as $point) {
            //     $leaderId = $point->leader_id;
            //     $leaderPoint = $point->leader_point;

            //     $point->mentor_id = $leaderId;
            //     $point->mentor_point = $leaderPoint;
            //     $point->leader_id = null;
            //     $point->leader_point = null;

            //     $point->save();
            // }

            // $answers = EmployeeAnswer::whereIn('review_id', [8,20,39,40,41])->whereIn('employee_id', [50,63,47])->get();
            // foreach ($answers as $answer) {
            //     $answer->type = 0.5;

            //     $answer->save();
            // }

            // DB::commit();
        } catch (Exception $e) {
            // DB::rollBack();
            DB::connection($connectionName)->rollBack();
            Log::error($e);
        }


        //insert question review
        // $questions = QuestionReview::where('period', 0)->where('type', 1)->get();
        // foreach ($questions as $question) {
        //     //insert employee answer question review
        //     EmployeeAnswer::create([
        //         'review_id' => 1, //Trang test
        //         'question_review_id' => $question->id,
        //         'employee_id' => 63, //Dawng support
        //         'type' => 1 //leader
        //     ]);
        // }

        // $points = EmployeeReviewPoint::where('review_id', 1)->get();
        // foreach ($points as $point) {
        //     $point->leader_id = 63;
        //     $point->save();
        // }
    }
}
