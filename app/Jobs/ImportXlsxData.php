<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Batchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Http\Controllers\api\CommonController;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Task;
use App\Models\TaskProject;
use App\Models\TaskTiming;
use App\Models\User;
use App\Models\Sticker;
use App\Models\Project;
use App\Models\Priority;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ImportXlsxData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,Batchable;

    public $data;
    public $headers = [
        0 => 'id',
        1 => 'name',
        2 => 'name',
        3 => 'name',
        4 => 'name',
        5 => 'name',
        6 => 'name',
        7 => 'name',
        8 => 'project_id',
        9 => 'department_id',
        10 => 'sticker_id',
        11 => 'priority',
        12 => 'weight',
        13 => 'user_id',
        14 => 'start_work_date',
        15 => 'end_work_date',
        16 => 'total_estimate_time',
        17 => 'total_time_spent',
        18 => 'progress',
        19 => 'status'
    ];

    public $colName = [
        1 => 'B',
        2 => 'C',
        3 => 'D',
        4 => 'E',
        5 => 'F',
        6 => 'G',
        7 => 'H'
    ];
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try { 
            $model = new Task();
            $connectionName = $model->getConnectionName();
            $file = $this->data;
            $projects = Project::select('id', 'name')->orderBy('ordinal_number', 'asc')->get()->toArray();

            //departments
            $constDepartments = config('const.departments_with_job');
            $departments = [];
            foreach ($constDepartments as $department) {
                $departments[$department['value']] = $department['label'];
            }

            //stickers
            $stickers = Sticker::select(
                'id',
                'name',
                'department_id',
                'level_1',
                'level_2',
                'level_3',
                'level_4',
                'level_5',
                'level_6',
                'level_7',
                'level_8',
                'level_9',
                'level_10',
            )->get()
            ->toArray();

            //priorities
            $priorities = Priority::select('id', 'label')->get()->toArray();

            //users
            $users = User::select('id', 'fullname')->get()->toArray();

            //status
            $constStatus = config('const.status');
            $status = [];
            foreach ($constStatus as $item) {
                $status[$item['value']] = $item['label'];
            }

            //action
            $actions = config('const.type_delete_task');
            
            $root_parent = null;
            $task_parent = null;
            $parents = [];
            $data = [];

            
            //start transaction control
            DB::connection($connectionName)->beginTransaction();

            foreach ($file as $key => $row) {
                if ($row[0]) {
                    $parents = $this->update(
                        $row,
                        $parents,
                        $projects,
                        $departments,
                        $stickers,
                        $priorities,
                        $users,
                        $status,
                        $actions
                    );
                } else {
                    $array = $this->store(
                        $row,
                        $parents,
                        [],
                        $projects,
                        $departments,
                        $stickers,
                        $priorities,
                        $users,
                        $status
                    );
                    $parents = $array['parents'];
                    //assign new id after insert
                    $rows[$key][0] = $array['task_id'];
                }
            }

            DB::connection($connectionName)->commit();
            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::connection($connectionName)->rollBack();
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    private function getDataFromFile($file)
    {
        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file);
        //File type check
        if ($inputFileType != "Xlsx") {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-016')
            ], Response::HTTP_NOT_FOUND);
        }

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($file);
        $reader->setLoadSheetsOnly('Tasks');

        $spreadsheet = $reader->load($file);
        
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // This loops through all cells,
            $cells = [];
            foreach ($cellIterator as $cell) {
                $cells[] = $cell->getValue();
            }
            $rows[] = $cells;
        }

        //remove first row that contains label header in file import
        array_splice($rows, 0, 1);

        return $rows;
    }

    private function update(
        $row,
        $parents,
        $projects,
        $departments,
        $stickers,
        $priorities,
        $users,
        $status,
        $actions
    ) {

        $isExist = false;
        $task = Task::where('id', $row[0])->first();
        if ($task) {
            $isExist = true;
        }

        for ($i = 0; $i < count($row); $i++) {
            switch ($i) {
                //name
                case 1:
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                case 7:
                    if ($row[$i]) {
                        $parents[$i] = $row[0];

                        if (!$isExist) {
                            return $parents;
                        }

                        //delete task if there is a call
                        if ($row[20]) {
                            $this->handleDeleteTasks($task, $row, $actions);
                            return $parents;
                        }

                        $column = $this->headers[$i];
                        $task->$column = $row[$i];

                        //generate code from task name
                        $task->code = $this->generateCodeFromName($row[$i]);

                        if ($i == 1) {
                            $task->root_parent = null;
                            $task->task_parent = null;
                        } else {
                            $rootParent = (int)$parents[1];
                            $taskParent = (int)$parents[$i-1];

                            //if the root parent was not deleted before we'll update this task, or not
                            $checkRoot = Task::where('id', $rootParent)->first();
                            if ($checkRoot) {
                                $task->root_parent = $rootParent;
                            }

                            //if the task parent was not deleted before we'll update this task, or not
                            $checkParent = Task::where('id', $taskParent)->first();
                            if ($checkParent) {
                                $task->task_parent = $taskParent;
                            }
                        }
                    }
                    break;
                //project_id
                // case 8:
                //     $projectNames = array_column($projects, 'name');
                //     $key = array_search($row[$i], $projectNames);

                //     $column = $this->headers[$i];
                //     $task->$column = is_int($key) ? $projects[$key]['id'] : null;

                //     break;
                //department_id
                case 9:
                    $key = array_search($row[$i], $departments);

                    $column = $this->headers[$i];
                    $task->$column = is_int($key) ? $key : null;

                    break;
                //sticker_id
                case 10:
                    $stickerNames = array_column($stickers, 'name');
                    $key = array_search($row[$i], $stickerNames);

                    $column = $this->headers[$i];
                    $task->$column = is_int($key) ? $stickers[$key]['id'] : null;

                    break;
                //priority
                case 11:
                    $priorityLabels = array_column($priorities, 'label');
                    $key = array_search($row[$i], $priorityLabels);

                    $column = $this->headers[$i];
                    $task->$column = is_int($key) ? $priorities[$key]['id'] : null;

                    break;
                //weight
                case 12:
                    $column = $this->headers[$i];

                    $stickerNames = array_column($stickers, 'name');
                    $key = array_search($row[10], $stickerNames);

                    if (is_int($key) && $row[11] &&
                         $stickers[$key]['department_id'] == $task->department_id) {
                        $task->$column = $stickers[$key]['level_'.$row[11]];
                    }

                    break;
                //user_id
                case 13:
                    $userFullnames = array_column($users, 'fullname');
                    $key = array_search($row[$i], $userFullnames);

                    $column = $this->headers[$i];
                    $task->$column = is_int($key) ? $users[$key]['id'] : null;

                    break;
                //start_work_date
                // case 14:
                //end_work_date
                case 15:
                    $this->insertWorkDateTaskTiming($task->id, [
                        'start_work_date' => $row[14],
                        'end_work_date' => $row[15],
                    ], false);

                    break;
                //total_estimate_time
                case 16:
                //total_time_spent
                case 17:
                    break;
                //progress
                case 18:
                    $column = $this->headers[$i];
                    $task->$column = $row[$i] ? $row[$i] : 0;

                    break;
                //status
                case 19:
                    $key = array_search($row[$i], $status);

                    $column = $this->headers[$i];
                    $task->$column = is_int($key) ? $key : null;

                    break;
                //action
                case 20:
                    // $key = array_search($row[$i], $status);

                    // $column = $this->headers[$i];
                    // $task->$column = is_int($key) ? $key : null;
                    break;
                default:
                    if ($isExist && !in_array($i, [8,14,15,16,17])) {
                        $column = $this->headers[$i];
                        $task->$column = $row[$i];
                    }
                    break;
            }
        }
        if ($isExist) {
            //insert
            if ($task->save()) {
                //update task_projects table
                $projectIds = $this->getProjectIdsImport($row[8], $projects);

                CommonController::syncTaskProjects($task, $projectIds);
            }
        }

        return $parents;
    }

    private function insertWorkDateTaskTiming($taskId, $duration, $isStore)
    {
        $startWorkDate = $duration['start_work_date'];
        $endWorkDate = $duration['end_work_date'];

        //start_work_date
        if ($this->isDate($startWorkDate)) {
            $startWorkDate = Carbon::createFromFormat('d/m/Y', $startWorkDate)->format('Y-m-d');
        } else {
            $startWorkDate = $startWorkDate ?
                date(
                    'Y-m-d',
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($startWorkDate)
                ) : null;
        }

        //end_work_date
        if ($this->isDate($endWorkDate)) {
            $endWorkDate = Carbon::createFromFormat('d/m/Y', $endWorkDate)->format('Y-m-d');
        } else {
            $endWorkDate = $endWorkDate ?
                date(
                    'Y-m-d',
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($endWorkDate)
                ) : null;
        }

        if (!empty($startWorkDate) && !empty($endWorkDate)) {
            $period = CarbonPeriod::create(
                Carbon::create($startWorkDate)->format('Y-m-d'),
                Carbon::create($endWorkDate)->format('Y-m-d')
            );

            $datesToCheck = collect($period)->map(function ($date) {
                return $date->format('Y-m-d');
            });
            $missingDates = $datesToCheck->reject(function ($date) use ($taskId) {
                return TaskTiming::where('work_date', $date)->where('task_id', $taskId)->exists();
            });
            $missingDates->each(function ($date) use ($taskId) {
                //insert to task_timings table
                TaskTiming::create([
                    'task_id' => $taskId,
                    'work_date' => $date,
                    'description' => 'This is from importing data.',
                    'type' => 0,
                ]);
            });
        } else {
            $workDate = null;

            if (!empty($startWorkDate) && empty($endWorkDate)) {
                $workDate = Carbon::create($startWorkDate)->format('Y-m-d');
            } elseif (empty($startWorkDate) && !empty($endWorkDate)) {
                $workDate = Carbon::create($endWorkDate)->format('Y-m-d');
            }

            $exist = TaskTiming::where('work_date', $workDate)->where('task_id', $taskId)->exists();
            if (!$exist) {
                 TaskTiming::create([
                    'task_id' => $taskId,
                    'work_date' => $workDate,
                    'description' => 'This is from importing data.',
                    'type' => 0
                ]);
            }
        }
    }

    private function store(
        $row,
        $parents,
        $saveData,
        $projects,
        $departments,
        $stickers,
        $priorities,
        $users,
        $status
    ) {
        $indexName = null;
        for ($i = 0; $i < count($row); $i++) {
            switch ($i) {
                //name
                case 1:
                case 2:
                case 3:
                case 4:
                case 5:
                case 6:
                case 7:
                    if ($row[$i]) {
                        $column = $this->headers[$i];
                        $saveData = [
                            $column => $row[$i],
                            //generate code from task name
                            'code' => $this->generateCodeFromName($row[$i])
                        ];

                        $indexName = $i;
                        $parents[$i] = $row[0];

                        if ($i == 1) {
                            //init saveData
                            $saveData = array_merge($saveData, [
                                'task_parent' => null,
                                'root_parent' => null,
                            ]);
                        } else {
                            $saveData = array_merge($saveData, [
                                'task_parent' => (int)$parents[$i-1],
                                'root_parent' => (int)$parents[1],
                            ]);
                        }
                    }
                    break;
                //project_id
                // case 8:
                //     $projectNames = array_column($projects, 'name');
                //     $key = array_search($row[$i], $projectNames);

                //     $column = $this->headers[$i];
                //     $saveData = array_merge($saveData, [
                //         $column => is_int($key) ? $projects[$key]['id'] : null
                //     ]);

                //     break;
                //department_id
                case 9:
                    $key = array_search($row[$i], $departments);

                    $column = $this->headers[$i];
                    $saveData = array_merge($saveData, [
                        $column => is_int($key) ? $key : null
                    ]);

                    break;
                //sticker_id
                case 10:
                    $stickerNames = array_column($stickers, 'name');
                    $key = array_search($row[$i], $stickerNames);

                    $column = $this->headers[$i];
                    $saveData = array_merge($saveData, [
                        $column => is_int($key) ? $stickers[$key]['id'] : null
                    ]);

                    break;
                //priority
                case 11:
                    $priorityLabels = array_column($priorities, 'label');
                    $key = array_search($row[$i], $priorityLabels);

                    $column = $this->headers[$i];
                    $saveData = array_merge($saveData, [
                        $column => is_int($key) ? $priorities[$key]['id'] : null
                    ]);

                    break;
                //weight
                case 12:
                    $column = $this->headers[$i];

                    $stickerNames = array_column($stickers, 'name');
                    $key = array_search($row[10], $stickerNames);

                    if (is_int($key) && $row[11] && $stickers[$key]['department_id'] == $saveData['department_id']) {
                        $saveData = array_merge($saveData, [
                            $column => $stickers[$key]['level_'.$row[11]]
                        ]);
                    }

                    break;
                //user_id
                case 13:
                    $userFullnames = array_column($users, 'fullname');
                    $key = array_search($row[$i], $userFullnames);

                    $column = $this->headers[$i];
                    $saveData = array_merge($saveData, [
                        $column => is_int($key) ? $users[$key]['id'] : null
                    ]);

                    break;
                //start_work_date
                case 14:
                //end_work_date
                case 15:
                    // if ($this->isDate($row[$i])) {
                    //     $datetime = Carbon::createFromFormat('d/m/Y', $row[$i])->format('Y-m-d');
                    // } else {
                    //     $datetime = $row[$i] ?
                    //         date('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($row[$i])) : null;
                    // }
                    // $column = $this->headers[$i];
                    // $saveData = array_merge($saveData, [
                    //     $column => $datetime
                    // ]);
                    // break;
                //total_estimate_time
                case 16:
                //total_time_spent
                case 17:
                    break;
                //progress
                case 18:
                    $column = $this->headers[$i];
                    $saveData = array_merge($saveData, [
                        $column => $row[$i] ? $row[$i] : 0
                    ]);

                    break;
                //status
                case 19:
                    $key = array_search($row[$i], $status);

                    $column = $this->headers[$i];
                    $saveData = array_merge($saveData, [
                        $column => is_int($key) ? $key : null
                    ]);

                    break;
                //action
                case 20:
                    break;
                default:
                    if (!in_array($i, [8,14,15,16,17])) {
                        $column = $this->headers[$i];
                        $saveData = array_merge($saveData, [
                            $column => $row[$i]
                        ]);
                    }
                    break;
            }
        }
        //insert tasks table
        $task = Task::create($saveData);

        //insert task_timings table
        $this->insertWorkDateTaskTiming($task->id, [
            'start_work_date' => $row[14],
            'end_work_date' => $row[15],
        ], true);

        //insert task_projects table
        $projectIds = $this->getProjectIdsImport($row[8], $projects);
        foreach ($projectIds as $value) {
            TaskProject::create([
                'task_id' => $task->id,
                'project_id' => $value,
            ]);
        }

        //assign new task id after insert successfully
        $parents[$indexName] = $task->id;

        return [
            'parents' => $parents,
            'task_id' => $task->id
        ];
    }

    private function getProjectIdsImport($value, $projects)
    {
        $projectImports = explode(',', $value);

        $projectIds = array_map(function ($projectImport) use ($projects) {
            $project = array_values(array_filter($projects, function ($project) use ($projectImport) {
                return $project['name'] === $projectImport;
            }));
            return !empty($project) ? $project[0]['id'] : null;
        }, $projectImports);

        return $projectIds;
    }

    private function handleDeleteTasks($task, $row, $actions)
    {
        //delete parent
        $greaterParent = $task->task_parent;
        $rootParent = $task->root_parent;
        if ($row[20] == $actions[2]) {
            $task->delete();

            //update task's child to greater parent
            $taskChild = Task::select('id')->where('task_parent', $row[0])->get();

            if (count($taskChild) > 0) {
                foreach ($taskChild as $value) {
                    $child = Task::find($value->id);
                    if ($child) {
                        $child->task_parent = $greaterParent;
                        $child->root_parent = $rootParent;
                        $child->save();

                        if (!$rootParent) {
                            $this->recursiveQueryAndUpdateOrDelete($child->id, true);
                        }
                    }
                }
            }
        //delete both of parent/child
        } elseif ($row[20] == $actions[1]) {
            // if ($greaterParent == null && $rootParent == null) {
            //     Task::where('root_parent', $task->id)->delete();
            // }
            $this->recursiveQueryAndUpdateOrDelete($task->id, false);
        }
    }

    private function recursiveQueryAndUpdateOrDelete($id, $isUpdate)
    {
        $sql  = " with recursive parent as (";
        $sql .= " select tasks.id from tasks";
        $sql .= " where tasks.id = ".$id." and tasks.deleted_at is null";
        $sql .= " union";
        $sql .= " select child.id from tasks child join parent parent on parent.id = child.task_parent";
        $sql .= " where child.deleted_at is null";

        if ($isUpdate) {
            $sql .= " ) select parent.id from parent where parent.id != ".$id;
        } else {
            $sql .= " ) select parent.id from parent";
        }

        $model = new Task();
        $result = DB::connection($model->getConnectionName())->select($sql);
        if (count($result) > 0) {
            $ids = collect($result)->pluck('id')->toArray();

            if ($isUpdate) {
                Task::whereIn('id', $ids)->update(['root_parent' => $id]);
            } else {
                Task::whereIn('id', $ids)->delete();

                TaskTiming::whereIn('task_id', $ids)->delete();
            }
        }
    }

    private function isDate($value)
    {
        if (!$value) {
            return false;
        } else {
            $date = date_parse_from_format('d/m/Y', $value);
            if ($date['error_count'] == 0 && $date['warning_count'] == 0) {
                return checkdate($date['month'], $date['day'], $date['year']);
            } else {
                return false;
            }
        }
    }

    private function generateCodeFromName($characters)
    {
        if (!$characters) {
            return null;
        }

        $words = preg_split("/[\s,_-]+/", $characters);
        $acronym = "";
        foreach ($words as $w) {
            $acronym .= mb_substr($w, 0, 1);
        }

        return mb_strtolower($acronym, 'UTF-8').'_'.Auth()->user()->id.'_'.time();
    }
}
