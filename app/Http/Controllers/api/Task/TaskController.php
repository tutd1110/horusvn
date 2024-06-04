<?php

namespace App\Http\Controllers\api\Task;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api\CommonController;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\TaskProject;
use App\Models\User;
use App\Models\Task;
use App\Models\TaskDeadline;
use App\Models\TaskFile;
use App\Models\TaskTiming;
use App\Models\TaskTimingProject;
use App\Models\Sticker;
use App\Models\Priority;
use App\Models\WeightedFluctuation;
use App\Http\Requests\api\Task\GetTaskListRequest;
use App\Http\Requests\api\Task\TaskRegisterRequest;
use App\Http\Requests\api\Task\GetTaskByIdRequest;
use App\Http\Requests\api\Task\TaskEditRequest;
use App\Http\Requests\api\Task\TaskQuickEditRequest;
use App\Http\Requests\api\Task\TaskMultipleEditRequest;
use App\Http\Requests\api\Task\TaskDeleteRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Task API
 *
 * @group Task
 */
class TaskController extends Controller
{
    /** Select boxes data
     *
     *
     * @group Task
     *
     *
     * @response 200 {
     *  [
     *      "departments": [
     *          {
     *              "value": 2,
     *              "label": "Dev",
     *              "short_name": "dev"
     *          },
     *          ...
     *      ],
     *      "projects": [
     *          {
     *              "id": 1,
     *              "project_name": "WZ 0.0.1"
     *          },
     *          {
     *              "id": 2,
     *              "project_name": "Battle Joke"
     *          },
     *          ...
     *      ],
     *  ]
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function getSelectboxes()
    {
        try {
            //list users by department with job
            // $departments = CommonController::getDepartmentsJob();
            $pmIdsRole = config('const.employee_id_pm_roles');
            $addPermission = config('const.employee_add_permission');

            $departments = config('const.departments');
            $departments = array_map(function ($id, $name) {
                return ['value' => $id, 'label' => $name];
            }, array_keys($departments), $departments);

            $dpIds = array_map(function ($department) {
                return $department['value'];
            }, $departments);
            $status = config('const.status');
            $projects = Project::select('id', 'name')->orderBy('ordinal_number', 'asc')->get();
            $priorities = Priority::select('id', 'label')->get();
            $users = User::select('id', 'fullname')
                        ->whereIn('department_id', $dpIds)
                        ->where('user_status', 1)->get();
            $is_authority = in_array(Auth()->user()->id, $pmIdsRole) || in_array(Auth()->user()->id, $addPermission);
            $session = Auth()->user();
            $data = [
                'projects' => $projects,
                'users' => $users,
                'status' => $status,
                'departments' => $departments,
                'priorities' => $priorities,
                'is_authority' => $is_authority,
                'session' => $session,
            ];

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Select boxes data for create/update task
     *
     *
     * @group Task
     *
     *
     * @response 200 {
     *  [
     *      "projects": [
     *          {
     *              "id": 1,
     *              "project_name": "WZ 0.0.1"
     *          },
     *          {
     *              "id": 2,
     *              "project_name": "Battle Joke"
     *          },
     *          ...
     *      ],
     *  ]
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function getSelectboxesForCreateUpdate()
    {
        try {
            $typeOfTask = config('const.type_of_task');
            //list users by department with job
            $departments = CommonController::getDepartmentsJob();
            $dpIds = array_map(function ($department) {
                return $department['value'];
            }, $departments);
            //priorities
            $priorities = Priority::select('id', 'label')->get()->toArray();
            //stickers
            $stickers = Sticker::select(
                'id',
                'name',
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
            )
            ->get();
            //status
            $status = config('const.status');
            $projects = Project::select('id', 'name')->orderBy('ordinal_number', 'asc')->get();
            $users = User::select('id', 'fullname')
                        ->whereIn('department_id', $dpIds)
                        ->where('position', '!=', 3)
                        ->where('user_status', '!=', 2)
                        ->get();

            $data = [
                'projects' => $projects,
                'users' => $users,
                'status' => $status,
                'departments' => $departments,
                'type_of_task' => $typeOfTask,
                'priorities' => $priorities,
                'stickers' => $stickers
            ];

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getSelectBoxesByDepartmentId(Request $request)
    {
        try {
            $requestDatas = $request->all();

            //employees
            $employees = User::withFilterByGroup()->select('id', 'fullname')
                            ->where('user_status', '!=', 2)
                            ->where(function ($query) use ($requestDatas) {
                                if (isset($requestDatas['department_id']) && !empty($requestDatas['department_id'])) {
                                    $query->whereIn('department_id', $requestDatas['department_id']);
                                }
                            })
                            ->where('position', '!=', 3)
                            ->get();

            return response()->json($employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    /** Tasks list for Manager or Admin
     *
     *
     * @group Tasks
     *
     * @bodyParam project_id bigint optional (Dự án)
     * @bodyParam user_id bigint optional (Người thực hiện)
     * @bodyParam start_time date optional (Ngày bắt đầu)
     * @bodyParam name string optional (Tên dự án)
     * @bodyParam department_id bigint optional (Bộ phận)
     * @bodyParam status integer optional (Trạng thái công việc)
     *
     * @response 200 {
     *  [
     *      {
     *          "id": 5,
     *          "name": "example name",
     *          "sticker_id": 1,
     *          "task_parent": null,
     *          "priority": 1,
     *          "start_time": "2023/04/07",
     *          "time": "7",
     *          "weight": 2,
     *          "department_id": 3,
     *          "user_id": 1200562615,
     *          "progress": 85,
     *          "status": 2,
     *          "grandchildren": []
     *      },
     *      ...
     *  ]
     * }
     * @response 404 {
     *    "errors": "Không có dữ liệu được tìm thấy"
     * }
     * @response 422 {
     *      "status": 422,
     *      "errors": "Dự án không tồn tại",
     *      "errors_list": {
     *          "project_id": [
     *              "Dự án không tồn tại"
     *          ]
     *      }
     * }
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function getTaskList(GetTaskListRequest $request)
    {
        try {
            ini_set('memory_limit', '2048M');

            $tasks = $this->getTasksBySqlSelectRaw($request);

            //no search results
            if (!$tasks) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            //employees
            $employees = User::withFilterByGroup()->where('user_status', '!=', 2)
                            ->where(function ($query) use ($request) {
                                if (!empty($request->department_id)) {
                                    $query->whereIn('department_id', $request->department_id);
                                }
                            })
                            ->where('position', '!=', 3)
                            ->get();

            //stickers
            $stickers = Sticker::select(
                'id',
                'name',
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
            )
            ->where(function ($query) use ($request) {
                if (!empty($request->department_id)) {
                    $query->whereIn('department_id', $request->department_id);
                }
            })
            ->get();

            $data = [
                'tasks' => $tasks,
                'employees' => $employees,
                'stickers' => $stickers
            ];

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Tasks with tree-data select dropdown
     *
     *
     * @group Tasks
     *
     * @bodyParam project_id bigint required (Dự án)
     *
     * @response 200 {
     *  [
     *      {
     *          "id": 5,
     *          "name": "example name",
     *          "grandchildren": []
     *      },
     *      ...
     *  ]
     * }
     * @response 422 {
     *      "status": 422,
     *      "errors": "Dự án không tồn tại",
     *      "errors_list": {
     *          "project_id": [
     *              "Dự án không tồn tại"
     *          ]
     *      }
     * }
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function getTasksWithTreeData(Request $request)
    {
        $parentIds = [];
        //Add SQL according to requested search conditions
        //On request
        $requestDatas = $request->all();
        if (empty($requestDatas['project_id']) || count($requestDatas['project_id']) == 0) {
            return $parentIds;
        }

        $parentIds = Task::join('task_projects', function ($join) use ($requestDatas) {
            $join->on('task_projects.task_id', '=', 'tasks.id')->whereNull('task_projects.deleted_at');
            if (isset($requestDatas['project_id']) && count($requestDatas['project_id']) > 0) {
                $projectIds = array_filter($requestDatas['project_id'], 'is_int');
                $join->whereIn('task_projects.project_id', $projectIds);
            }
        })
        ->select('tasks.id', 'tasks.root_parent')
        ->get();

        //return if there is no parent id
        if (count($parentIds) == 0) {
            return $parentIds;
        }

        $arrayId = [];
        //convert parentIds to array
        foreach ($parentIds as $value) {
            if (!empty($value->root_parent) || $value->root_parent != null) {
                $arrayId[] = $value->root_parent;
            } else {
                $arrayId[] = $value->id;
            }
        }

        //convert parent ids to string
        $stringIds = implode(",", array_unique($arrayId));

        //get list tasks by list parent id
        $sql = "";
        $sql .= "WITH RECURSIVE parent AS (";

        $sql .= "SELECT";
        $sql .= " tasks.id, tasks.name, tasks.task_parent";
        $sql .= " FROM tasks";
        $sql .= " WHERE tasks.id IN (".$stringIds.")";
        $sql .= " AND tasks.deleted_at is null";

        $sql .= " UNION ";

        $sql .= " SELECT";
        $sql .= " child.id, child.name, child.task_parent";
        $sql .= " FROM tasks child";
        $sql .= " JOIN parent parent ON parent.id = child.task_parent";
        $sql .= " WHERE child.deleted_at is null";

        $sql .= ")";

        $sql .= "SELECT * FROM parent";
        if ($request->id) {
            $sql .= " WHERE parent.id != ".$request->id;
        }

        $task = new Task();
        $result = DB::connection($task->getConnectionName())->select($sql);

        $data = $this->createHierarchicalTree($result);

        if (!$data) {
            return [];
        }

        return response()->json($data);
    }

    /** Task Store
     *
     * @group Task
     *
     *
     * @bodyParam name string required Tên công việc
     * @bodyParam code string required Mã công việc
     * @bodyParam description longtext required Thông tin công việc
     * @bodyParam start_time date required Ngày bắt đầu công việc
     * @bodyParam task_parent biginteger optional Công việc cha
     * @bodyParam project_id biginteger required Dự án
     *
     * @response 404 {
     *    'status' : 404,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 422 {
     *      "status": 422,
     *      "errors": "Dự án không tồn tại",
     *      "errors_list": {
     *          "project_id": [
     *              "Dự án không tồn tại"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function store(TaskRegisterRequest $request)
    {
        $requestDatas = $request->all();

        try {
            //find root_parent by task_parent
            $root_parent = null;
            $task_parent = null;
            if (!empty($requestDatas['task_parent'])) {
                $task_parent = $requestDatas['task_parent'];
                $root_parent = $this->getRootParentId($task_parent);
            }

            $name = isset($requestDatas['name']) ? $requestDatas['name'] : null;
            //init saveData
            $saveData = [
                'name' => $name,
                'code' => $this->generateCodeFromName($name),
                'description' => $requestDatas['description'],
                'task_parent' => $task_parent,
                'root_parent' => $root_parent,
            ];

            //create save data when type is child
            if ($requestDatas['type'] == 'child') {
                $saveData = array_merge($saveData, [
                    'weight' => $requestDatas['weight'],
                    'priority' => $requestDatas['priority'],
                    'sticker_id' => $requestDatas['sticker_id'],
                    'department_id' => $requestDatas['department_id'],
                    'status' => $requestDatas['status'],
                    'user_id' => $requestDatas['user_id']
                ]);
            }

            $model = new Task();
            $connectionName = $model->getConnectionName();
            //start transaction control
            DB::connection($connectionName)->beginTransaction();

            $task = Task::create($saveData);

            //insert task_projects table
            if (isset($requestDatas['project_ids']) && count($requestDatas['project_ids']) > 0) {
                foreach ($requestDatas['project_ids'] as $value) {
                    TaskProject::create([
                        'task_id' => $task->id,
                        'project_id' => $value,
                    ]);
                }
            }

            $this->insertWorkDateTaskTiming($task->id, [
                'start_time' => isset($requestDatas['start_time']) ? $requestDatas['start_time'] : "",
                'end_time' => isset($requestDatas['end_time']) ? $requestDatas['end_time'] : "",
            ]);

            //clone the task if it has many childs
            if ($requestDatas['clone_id']) {
                $cloneId = $requestDatas['clone_id'];

                $sqlJoinRaw = " JOIN parent parent ON parent.id = child.task_parent";

                $childs = $this->fullRecursiveSql($sqlJoinRaw, $cloneId);

                $oldParentIds = [];
                foreach ($childs as $child) {
                    $oldParentIds[$cloneId] = $task->id;

                    if ($child->id == $cloneId) {
                        continue;
                    }

                    $newChildId = Task::create([
                        'name' => $child->name,
                        'code' => $this->generateCodeFromName($child->name),
                        'description' => $child->description,
                        'priority' => $child->priority,
                        'sticker_id' => $child->sticker_id,
                        'department_id' => $child->department_id,
                        'weight' => $child->weight,
                        'project_id' => $task->project_id,
                        'task_parent' => $oldParentIds[$child->task_parent],
                        'user_id' => $child->user_id,
                        'status' => $child->status,
                        'root_parent' => $root_parent ? $root_parent : $task->id,
                        'progress' => $child->progress
                    ]);

                    $taskTimings = TaskTiming::select('id', 'work_date', 'estimate_time', 'time_spent')
                                    ->where(function ($query) use ($requestDatas) {
                                        if (!empty($requestDatas['start_time'])) {
                                            $query->whereDate(
                                                'work_date',
                                                '>=',
                                                Carbon::create($requestDatas['start_time'])->format('Y/m/d')
                                            );
                                        }
                                        if (!empty($requestDatas['end_time'])) {
                                            $query->whereDate(
                                                'work_date',
                                                '<=',
                                                Carbon::create($requestDatas['end_time'])->format('Y/m/d')
                                            );
                                        }
                                    })
                                    ->where('task_id', $child->id)
                                    ->where('type', 0)
                                    ->get();

                    foreach ($taskTimings as $taskTiming) {
                        $date = $taskTiming->work_date ? Carbon::createFromFormat('d/m/Y', $taskTiming->work_date)
                            : null;
                        //insert to task_timings table
                        TaskTiming::create([
                            'task_id' => $newChildId->id,
                            'work_date' => $date ? $date->format('Y/m/d') : null,
                            'type' => 0
                        ]);
                    }

                    //make a copy for task_projects tables
                    $taskProjects = TaskProject::where('task_id', $child->id)->get();
                    foreach ($taskProjects as $item1) {
                        TaskProject::create([
                            'task_id' =>  $newChildId->id,
                            'project_id' => $item1->project_id,
                        ]);
                    }

                    $oldParentIds[$child->id] = $newChildId->id;
                }
            }

            DB::connection($connectionName)->commit();
        } catch (Exception $e) {
            DB::connection($connectionName)->rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function insertWorkDateTaskTiming($taskId, $duration)
    {
        if (!empty($duration['start_time']) && !empty($duration['end_time'])) {
            $period = CarbonPeriod::create(
                Carbon::create($duration['start_time'])->format('Y-m-d'),
                Carbon::create($duration['end_time'])->format('Y-m-d')
            );
            foreach ($period->toArray() as $item) {
                //insert to task_timings table
                TaskTiming::create([
                    'task_id' => $taskId,
                    'work_date' => $item->format('Y-m-d'),
                    'type' => 0
                ]);
            }
        } else {
            $workDate = null;

            if (!empty($duration['start_time']) && empty($duration['end_time'])) {
                $workDate = Carbon::create($duration['start_time'])->format('Y-m-d');
            } elseif (empty($duration['start_time']) && !empty($duration['end_time'])) {
                $workDate = Carbon::create($duration['end_time'])->format('Y-m-d');
            }

            //insert to task_timings table
            TaskTiming::create([
                'task_id' => $taskId,
                'work_date' => $workDate,
                'type' => 0
            ]);
        }
    }

    /** Update Task
     *
     * @group Task
     *
     * @bodyParam id bigint required ID Công việc
     * @bodyParam name string required Tên công việc
     * @bodyParam code string required Mã công việc
     * @bodyParam description longtext required Thông tin công việc
     * @bodyParam task_parent biginteger required Công việc cha
     * @bodyParam project_id biginteger required Dự án
     *
     * * @response 400 {
     *    'status' : 400,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 404 {
     *    'status' : 404,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 422 {
     *      "status": 422,
     *      "errors": "Dự án không tồn tại",
     *      "errors_list": {
     *          "project_id": [
     *              "Dự án không tồn tại"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function update(TaskEditRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            Task::performTransaction(function ($model) use ($requestDatas) {
                $attributesChanged = $this->doUpdate($requestDatas['id'], $requestDatas);

                if (is_array($requestDatas['id_list']) && count($requestDatas['id_list']) > 0) {
                    $index = array_search($requestDatas['id'], $requestDatas['id_list']);
                    if ($index !== false) {
                        array_splice($requestDatas['id_list'], $index, 1);
                    }

                    foreach ($requestDatas['id_list'] as $id) {
                        $this->doMultipleUpdate($id, $requestDatas, $attributesChanged);
                    }
                }
            });
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
        }
    }

    private function doUpdate($id, $requestDatas)
    {
        try {
            $attributesChanged = [];

            $task = Task::findOrFail($id);

            //exclusion control
            // $task->setCheckUpdatedAt($requestDatas['check_updated_at']);

            //find root_parent by task_parent
            $root_parent = null;
            $task_parent = null;
            if (!empty($requestDatas['task_parent'])) {
                $task_parent = $requestDatas['task_parent'];

                //check relation tasks
                $check = $this->checkRelationTask($task_parent, $task->id);
                if (!$check) {
                    return response()->json(
                        ['status' => Response::HTTP_NOT_FOUND,
                        'errors' => __('MSG-E-012')
                        ],
                        Response::HTTP_NOT_FOUND
                    );
                }

                //detect new task_parent value
                $task->task_parent = $requestDatas['task_parent'];
                if ($task->isDirty('task_parent')) {
                    $root_parent = $this->getRootParentId($task_parent);
                }
            }

            //remove task_parent from task's child
            if (is_null($requestDatas['task_parent'])) {
                $task->task_parent = null;
                $task->root_parent = null;
            }

            //assign the attribute task_parent if it has been changed
            if ($task->isDirty('task_parent')) {
                $attributesChanged['task_parent'] = true;
            }

            $name = isset($requestDatas['name']) ? $requestDatas['name'] : null;
            //init update data
            $task->name = $name;
            if ($task->isDirty('name')) {
                $task->code = $this->generateCodeFromName($name);
            }

            $task->description = $requestDatas['description'];
            if ($root_parent) {
                $task->root_parent = $root_parent;
            }

            if ($requestDatas['type'] == 'child') {
                $task->weight = $requestDatas['weight'];
                //assign the attribute root_parent if it has been changed
                if ($task->isDirty('weight')) {
                    $attributesChanged['weight'] = true;

                    CommonController::updateWeightTaskProjects($task);
                }
                $task->priority = $requestDatas['priority'];
                //assign the attribute root_parent if it has been changed
                if ($task->isDirty('priority')) {
                    $attributesChanged['priority'] = true;
                }
                $task->sticker_id = $requestDatas['sticker_id'];
                //assign the attribute root_parent if it has been changed
                if ($task->isDirty('sticker_id')) {
                    $attributesChanged['sticker_id'] = true;
                }
                $task->department_id = $requestDatas['department_id'];
                //assign the attribute root_parent if it has been changed
                if ($task->isDirty('department_id')) {
                    $attributesChanged['department_id'] = true;
                }
                $task->status = $requestDatas['status'];
                //assign the attribute root_parent if it has been changed
                if ($task->isDirty('status')) {
                    $attributesChanged['status'] = true;
                }
                if ($requestDatas['status'] == 4) {
                    //if status is 4 (completed), progress will be 100
                    $task->progress = 100;
                }
                $task->user_id = $requestDatas['user_id'];
                //assign the attribute root_parent if it has been changed
                if ($task->isDirty('user_id')) {
                    $attributesChanged['user_id'] = true;
                }
            }

            if ($task->isDirty('task_parent')) {
                // update root_parent for task's child
                $sqlJoinRaw = " JOIN parent parent ON parent.id = child.task_parent";
                $childs = $this->fullRecursiveSqlWithIdParentColumns($sqlJoinRaw, $task->id);

                if (count($childs) > 1) {
                    $ids = [];
                    foreach ($childs as $child) {
                        $ids[] = $child->id;
                    }

                    Task::where('id', '!=', $task->id)->whereIn('id', $ids)
                        ->update(['root_parent' => $root_parent ? $root_parent : $task->id]);
                }
                //end
            }

            //insert task
            if ($task->save()) {
                $attributesChanged['project_id'] = CommonController::syncTaskProjects(
                    $task,
                    $requestDatas['project_ids']
                );

                TaskDeadline::where('task_id', $task->id)->update(['status' => $task->status]);
            }

            return $attributesChanged;
        } catch (ExclusiveLockException $e) {
            // Re-throw the exception to propagate it up the call stack
            throw $e;
        } catch (Exception $e) {
            // Re-throw the exception to propagate it up the call stack
            throw $e;
        }
    }

    private function doMultipleUpdate($id, $requestDatas, $attributesChanged)
    {
        try {
            $task = Task::findOrFail($id);

            if (isset($attributesChanged['weight']) && $attributesChanged['weight']) {
                $task->weight = $requestDatas['weight'];
                if ($task->isDirty('weight')) {
                    CommonController::updateWeightTaskProjects($task);
                }
                $task->save();
            }

            if (isset($attributesChanged['priority']) && $attributesChanged['priority']) {
                $task->priority = $requestDatas['priority'];
                $task->save();
            }

            if (isset($attributesChanged['sticker_id']) && $attributesChanged['sticker_id']) {
                $task->sticker_id = $requestDatas['sticker_id'];
                $task->save();
            }

            if (isset($attributesChanged['department_id']) && $attributesChanged['department_id']) {
                $task->department_id = $requestDatas['department_id'];
                $task->save();
            }

            if (isset($attributesChanged['status']) && $attributesChanged['status']) {
                $task->status = $requestDatas['status'];
                if ($requestDatas['status'] == 4) {
                    //if status is 4 (completed), progress will be 100
                    $task->progress = 100;
                }
                $task->save();
            }

            if (isset($attributesChanged['user_id']) && $attributesChanged['user_id']) {
                $task->user_id = $requestDatas['user_id'];
                $task->save();
            }

            if (isset($attributesChanged['task_parent']) && $attributesChanged['task_parent']) {
                //find root_parent by task_parent
                $root_parent = null;
                $task_parent = null;
                if (!empty($requestDatas['task_parent'])) {
                    $task_parent = $requestDatas['task_parent'];

                    //check relation tasks
                    $check = $this->checkRelationTask($task_parent, $task->id);
                    if (!$check) {
                        return response()->json(
                            ['status' => Response::HTTP_NOT_FOUND,
                            'errors' => __('MSG-E-012')
                            ],
                            Response::HTTP_NOT_FOUND
                        );
                    }

                    //detect new task_parent value
                    $task->task_parent = $requestDatas['task_parent'];
                    if ($task->isDirty('task_parent')) {
                        $root_parent = $this->getRootParentId($task_parent);
                    }
                }

                //remove task_parent from task's child
                if (is_null($requestDatas['task_parent'])) {
                    $task->task_parent = null;
                    $task->root_parent = null;
                }

                if ($root_parent) {
                    $task->root_parent = $root_parent;
                }

                $task->save();

                // update root_parent for task's child
                $sqlJoinRaw = " JOIN parent parent ON parent.id = child.task_parent";
                $childs = $this->fullRecursiveSqlWithIdParentColumns($sqlJoinRaw, $task->id);

                if (count($childs) > 1) {
                    $ids = [];
                    foreach ($childs as $child) {
                        $ids[] = $child->id;
                    }

                    Task::where('id', '!=', $task->id)->whereIn('id', $ids)
                        ->update(['root_parent' => $root_parent ? $root_parent : $task->id]);
                }
                //end
            }

            //insert task projecs
            if ($attributesChanged['project_id']) {
                CommonController::syncTaskProjects($task, $requestDatas['project_ids']);
            }
        } catch (ExclusiveLockException $e) {
            // Re-throw the exception to propagate it up the call stack
            throw $e;
        } catch (Exception $e) {
            // Re-throw the exception to propagate it up the call stack
            throw $e;
        }
    }

    public function quickUpdate(TaskQuickEditRequest $request)
    {
        try {
            $requestDatas = $request->all();

            $model = new Task();
            $connectionName = $model->getConnectionName();
            //start transaction control
            DB::connection($connectionName)->beginTransaction();

            $task = Task::findOrFail($requestDatas['id']);

            //find root_parent by task_parent
            $root_parent = null;
            $task_parent = null;
            if (isset($requestDatas['task_parent']) && !empty($requestDatas['task_parent'])) {
                $task_parent = $requestDatas['task_parent'];

                //check relation tasks
                $check = $this->checkRelationTask($task_parent, $task->id);
                if (!$check) {
                    return response()->json(
                        ['status' => Response::HTTP_NOT_FOUND,
                        'errors' => __('MSG-E-012')
                        ],
                        Response::HTTP_NOT_FOUND
                    );
                }

                //detect new task_parent value
                $task->task_parent = $requestDatas['task_parent'];
                if ($task->isDirty('task_parent')) {
                    $root_parent = $this->getRootParentId($task_parent);
                }
            }

            if (array_key_exists('name', $requestDatas)) {
                $name = isset($requestDatas['name']) ? $requestDatas['name'] : null;

                $task->name = $name;
                if ($task->isDirty('name')) {
                    $task->code = $this->generateCodeFromName($name);
                }
            }

            if (array_key_exists('project_ids', $requestDatas)) {
                CommonController::syncTaskProjects($task, $requestDatas['project_ids']);
            }

            if (array_key_exists('sticker_id', $requestDatas)) {
                $task->sticker_id = $requestDatas['sticker_id'];
            }

            if (array_key_exists('priority', $requestDatas)) {
                $task->priority = $requestDatas['priority'];
            }

            if (array_key_exists('department_id', $requestDatas)) {
                $task->department_id = $requestDatas['department_id'];
            }

            if (array_key_exists('user_id', $requestDatas)) {
                $task->user_id = $requestDatas['user_id'];
            }

            if (array_key_exists('weight', $requestDatas)) {
                $task->weight = $requestDatas['weight'];

                if ($task->isDirty('weight')) {
                    CommonController::updateWeightTaskProjects($task);
                }
            }

            if (array_key_exists('progress', $requestDatas)) {
                $task->progress = $requestDatas['progress'] ? $requestDatas['progress'] : 0;
            }
            if (array_key_exists('quality', $requestDatas)) {
                $task->quality = $requestDatas['quality'] ? $requestDatas['quality'] : 0;
            }

            if (array_key_exists('deadline', $requestDatas)) {
                $task->deadline = $requestDatas['deadline'] ? $requestDatas['deadline'] : null;
            }

            if (array_key_exists('status', $requestDatas)) {
                $task->status = $requestDatas['status'];
                if ($requestDatas['status'] == 4) {
                    //if status is 4 (completed), progress will be 100
                    $task->progress = 100;

                    //save weighted fluctuations
                    $isNotCompleted = $this->saveWeightedFluctuations($task);
                    if ($isNotCompleted) {
                        return response()->json(
                            ['status' => Response::HTTP_NOT_FOUND,
                            'errors' => __('MSG-E-022')
                            ],
                            Response::HTTP_NOT_FOUND
                        );
                    }
                }

                TaskDeadline::where('task_id', $task->id)->update(['status' => $task->status]);
            }

            if ($root_parent) {
                $task->root_parent = $root_parent;
            }

            //insert task
            $task->save();

            DB::connection($connectionName)->commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::connection($connectionName)->rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function updateMultiple(TaskMultipleEditRequest $request)
    {
        try {
            $requestDatas = $request->all();

            if($requestDatas['id'] && $requestDatas['multiple_status']){
                $taskUpdates = [
                    'status' => $requestDatas['multiple_status'],
                ];
                if ($requestDatas['multiple_status'] == 4) {
                    $taskUpdates['progress'] = 100;
                }
                $task = Task::whereIn('id', $requestDatas['id'])->update($taskUpdates);
                if ($task) {
                    TaskDeadline::whereIn('task_id', $requestDatas['id'])->update(['status' => $requestDatas['multiple_status']]);
                }
                return response()->json([
                    'success' => __('MSG-S-001'),
                ], Response::HTTP_OK);
            }

        } catch (Exception $e) {
            DB::connection($connectionName)->rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function saveWeightedFluctuations($task)
    {
        $isNotCompleted = false;

        $issues = TaskTiming::join('task_assignments', function ($join) {
            $join->on('task_assignments.id', '=', 'task_timings.task_assignment_id')
                ->whereNull('task_assignments.deleted_at');
        })
        ->select(
            'task_timings.weight',
            'task_assignments.assigned_user_id',
            'task_assignments.tester_id',
            'task_assignments.type',
            'task_assignments.status'
        )
        ->where('task_timings.task_id', $task->id)->get();

        // $weightedLeft = $task->weight;
        if (count($issues) > 0) {
            foreach ($issues as $item) {
                if ($item->assigned_user_id != $task->id) {
                    if ($item->status != 5) {//Confirmed
                        $isNotCompleted = true;

                        break;
                    }

                    if ($item->weight > 0) {
                        // $weightedLeft -= ($item->weight)*2;

                        WeightedFluctuation::insert([
                            [
                                'user_id' => $item->assigned_user_id,
                                'task_id' => $task->id,
                                'weight' => $item->weight,
                                'type' => 1, //the weight will be added for employees, who do the task's origin employee
                                'issue' => $item->type,
                                'created_at' => now(),
                                'updated_at' => now()
                            ],
                            // [
                            //     'user_id' => $item->tester_id,
                            //     'task_id' => $task->id,
                            //     'weight' => $item->weight,
                            //     'type' => 0, //the weight will be added for testers,
                            //     'issue' => $item->type,
                            //     'created_at' => now(),
                            //     'updated_at' => now()
                            // ]
                        ]);
                    }
                }
            }
            WeightedFluctuation::create([
                'user_id' => $task->user_id,
                'task_id' => $task->id,
                'weight' => -($item->weight),
                'type' => 2, //the weight left after changed for origin employees task
                'issue' => null
            ]);
        }

        return $isNotCompleted;
    }

    /** Delete task
     *
     * @group Task
     *
     * @bodyParam id bigint required ID Công việc
     * @bodyParam check_updated_at date required
     *
     * * @response 400 {
     *    'status' : 400,
     *    "errors": "Dữ liệu đã thay đổi ở thiết bị khác và không thể xoá"
     * }
     *
     * @response 404 {
     *    'status' : 404,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 422 {
     *    'status' : 422,
     *    "errors": "Ngày giờ sửa đổi không được để trống",
     *    "errors_list": {
     *          "check_updated_at": [
     *              "Ngày giờ sửa đổi không được để trống"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function destroy(TaskDeleteRequest $request)
    {
        $requestDatas = $request->all();

        try {
            $task = Task::findOrFail($requestDatas['id']);
            $id = $task->id;

            //exclusion control
            $task->setCheckUpdatedAt($requestDatas['check_updated_at']);

            Task::performTransaction(function ($model) use ($task, $id) {
                //delete project
                if ($task->delete()) {
                    // Delete task timing projects
                    TaskTimingProject::where('task_id', $id)->delete();
                }
            });
        } catch (ExclusiveLockException $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'errors' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteMultiple(Request $request)
    {
        $requestDatas = $request->all();

        try {
            // Retrieve the resources with the given IDs
            $resourcesToDelete = Task::whereIn('id', $requestDatas['ids'])->get();

            // Verify that the number of retrieved resources matches the number of requested IDs
            if ($resourcesToDelete->count() !== count($requestDatas['ids'])) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $model = new Task();
            $connectionName = $model->getConnectionName();
            //start transaction control
            DB::connection($connectionName)->beginTransaction();

            //delete tasks with parent and its childs
            if ($requestDatas['mode'] == 2) {
                // Delete tasks with the given IDs
                Task::whereIn('id', $requestDatas['ids'])->delete();
                // Delete task files with the given IDs
                TaskFile::whereIn('task_files.task_id', $requestDatas['ids'])->delete();
                // Delete task_timings with the given IDs
                TaskTiming::whereIn("task_timings.task_id", $requestDatas['ids'])->delete();
                //delete task projects
                TaskProject::whereIn("task_projects.task_id", $requestDatas['ids'])->delete();
                // Delete task timing projects
                TaskTimingProject::whereIn('task_id', $requestDatas['ids'])->delete();
            } elseif ($requestDatas['mode'] == 1) {
            //delete tasks with only parent and keep its childs
                foreach ($requestDatas['ids'] as $id) {
                    $task = Task::findOrFail($id);

                    $taskParent = $task->task_parent;
                    $rootParent = $task->root_parent;

                    if ($task->delete()) {
                        //delete task timing projects
                        TaskTimingProject::where('task_id', $id)->delete();
                    }

                    //update task's child to greater parent
                    $taskChild = Task::select('id')->where('task_parent', $id)->get();

                    if (count($taskChild) > 0) {
                        foreach ($taskChild as $value) {
                            $child = Task::find($value->id);
                            if ($child) {
                                $child->task_parent = $taskParent;
                                $child->root_parent = $rootParent;
                                $child->save();

                                $this->updateRootParent($rootParent, $child->id);
                            }
                        }
                    }
                }
            }

            DB::connection($connectionName)->commit();
        } catch (ExclusiveLockException $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'errors' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            DB::connection($connectionName)->rollBack();
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function updateRootParent($rootParent, $id)
    {
        if ($rootParent) {
            return true;
        }

        $sqlJoinRaw = " JOIN parent parent ON parent.id = child.task_parent";
        $childs = $this->fullRecursiveSqlWithIdParentColumns($sqlJoinRaw, $id);

        if (count($childs) > 0) {
            $ids = collect($childs)->slice(1)->pluck('id')->toArray();

            Task::whereIn('id', $ids)->update(['root_parent' => $id]);
        }
    }

    /** Task
     *
     * Get task by id
     *
     * @group Task
     *
     * @bodyParam id bigint required (task id)
     *
     * @response 200 {
     *  [
     *      {
     *          "id": "1",
     *          "name": "World War 2.0",
     *          "code: "WW20",
     *          "start_time": "2022-12-01 00:00:00",
     *          "time" : 1,
     *          "weight": 1,
     *          "description": "<ol><li>Nothing</li><li>Else</li></ol>",
     *          "priority": 1,
     *          "sticker_id": 2,
     *          "department_id": 2,
     *          "status": 3,
     *          "project_id": 2,
     *          "task_parent": 1,
     *          "user_ids": 1
     *      },
     *  ]
     * }
     * @response 404 {
     *    "status": 404,
     *    "errors": "Công việc không tồn tại"
     * }
     * @response 422 {
     *      "status": 422,
     *      "errors": "Công việc không tồn tại",
     *      "errors_list": {
     *          "id": [
     *              "Công việc không tồn tại"
     *          ]
     *      }
     * }
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function getTaskById(GetTaskByIdRequest $request)
    {
        try {
            $subquery = TaskProject::select('task_id', DB::raw('array_agg(project_id) as project_ids'))
                    ->whereNull('deleted_at')
                    ->groupBy('task_id');

            $task = Task::join('task_timings', function ($join) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');
            })
            ->leftJoinSub($subquery, 'pj', function ($join) {
                $join->on('pj.task_id', '=', 'tasks.id');
            })
            ->selectRaw(DB::raw("
                tasks.id as id,
                tasks.name as name,
                tasks.sticker_id as sticker_id,
                coalesce(nullif(pj.project_ids, '{null}'), null) as project_id,
                min(task_timings.work_date) as start_time,
                max(task_timings.work_date) as end_time,
                tasks.description as description,
                tasks.status as status,
                tasks.weight as weight,
                tasks.priority as priority,
                tasks.department_id as department_id,
                tasks.task_parent as task_parent,
                tasks.user_id as user_id,
                tasks.updated_at as updated_at"))
            ->where('tasks.id', $request->id)->groupBy('tasks.id', 'pj.project_ids')->first();

            $data = [
                "id" => $task->id,
                "type" => "child",
                "name" => $task->name,
                "start_time" => $task->start_time ? Carbon::create($task->start_time)->format('d/m/Y') : null,
                "end_time" => $task->end_time ? Carbon::create($task->end_time)->format("d/m/Y") : null,
                "weight" => $task->weight,
                "priority" => $task->priority,
                "sticker_id" => $task->sticker_id,
                "department_id" => $task->department_id,
                "status" => $task->status,
                "task_parent" => $task->task_parent,
                "user_id" => $task->user_id,
                "description" => $task->description,
                "project_id" => $task->project_id,
                "updated_at" => $task->updated_at
            ];

            $taskChild = Task::where('tasks.task_parent', $task->id)->whereNull('deleted_at')->get();

            if (!$task->task_parent && count($taskChild) > 0) {
                $data["type"] = "parent";
            }

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
        }
    }

    /** Get root_parent by task_parent id
     *
     * @param  $id
     * @return $root_parent
    */
    private function getRootParentId($id)
    {
        $sql = "";
        $sql .= "WITH RECURSIVE parent AS (";

        $sql .= "SELECT";
        $sql .= " tasks.id, tasks.task_parent";
        $sql .= " FROM tasks";
        $sql .= " WHERE tasks.id = ".$id;

        $sql .= " UNION ALL";

        $sql .= " SELECT";
        $sql .= " t.id, t.task_parent";
        $sql .= " FROM parent";
        $sql .= " INNER JOIN tasks t ON t.id = parent.task_parent";

        $sql .= ")";

        $sql .= "SELECT id FROM parent WHERE task_parent is null";

        $task = new Task();
        $data = DB::connection($task->getConnectionName())->select($sql);

        return $data[0]->id;
    }

    /** Append SQL Raw if json is requested
     *
     * @param  $request
     * @return $tasks
    */
    private function getTasksBySqlSelectRaw($request)
    {
        //On request
        $requestDatas = $request->all();

        $filteredData = array_filter($requestDatas);
        $isOnlySearchIds = false;
        $stringSearchIds = isset($requestDatas['ids']) && count($requestDatas['ids']) > 0
            ? implode(',', $requestDatas['ids']) : null;
        $tree = [];

        //sql join parent and child
        if (count($filteredData) === 1 && isset($filteredData['ids'])) {
            $isOnlySearchIds = true;
            $sqlRaw = " JOIN parent parent ON parent.id = child.task_parent";

            $result = $this->recursiveSql($sqlRaw, $stringSearchIds, $requestDatas, $isOnlySearchIds);
        } else {
            //get list tasks id
            $query = Task::join('task_timings', function ($join) use ($requestDatas) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');

                if (!empty($requestDatas['start_time']) && !empty($requestDatas['end_time'])) {
                    $join->whereDate(
                        'task_timings.work_date',
                        '>=',
                        Carbon::create($requestDatas['start_time'])->format('Y/m/d')
                    );
                    $join->whereDate(
                        'task_timings.work_date',
                        '<=',
                        Carbon::create($requestDatas['end_time'])->format('Y/m/d')
                    );
                }
            })
            ->leftJoin('task_projects', function ($join) use ($requestDatas) {
                $join->on('task_projects.task_id', '=', 'tasks.id')->whereNull('task_projects.deleted_at');

                if(!empty($requestDatas['exclude_project_ids'])){
                    $join = $join->whereNotIn('task_projects.project_id',$requestDatas['exclude_project_ids']);
                }

                if (isset($requestDatas['project_id']) && count($requestDatas['project_id']) > 0) {
                    $projectIds = array_filter($requestDatas['project_id']);

                    $join->where(function ($groupQuery) use ($requestDatas, $projectIds) {
                        if (!empty($projectIds)) {
                            $groupQuery->whereIn('task_projects.project_id', $projectIds);
                        }

                        if (in_array(0, $requestDatas['project_id'])) {
                            $groupQuery->orWhereNull('task_projects.project_id');
                        }
                    });
                }
            })
            ->select('tasks.id');
            //Add SQL according to requested search conditions
            if(!empty($requestDatas['exclude_project_ids'])){
                $query = $query->whereNotIn('task_projects.project_id',$requestDatas['exclude_project_ids']);
            }

            if (!empty($requestDatas)) {
                //get json from request
                $query = $this->addSqlWithSorting($requestDatas, $query);
            }
            $parentIds = $query->get();

            if (count($parentIds->toArray()) == 0) {
                return null;
            }

            $listSearchIds = $parentIds->toArray();
            $listSearchIds = array_unique($listSearchIds, SORT_REGULAR);

            $stringIds = implode(",", array_column($listSearchIds, 'id'));

            $sqlRaw = " JOIN parent parent ON parent.task_parent = child.id";

            $result = $this->recursiveSql($sqlRaw, $stringIds, $requestDatas, $isOnlySearchIds, $stringSearchIds);
        }

        if (isset($requestDatas['ids']) && count($requestDatas['ids']) > 0) {
            $originParentIds = [];
            $parentIds = array_column($result, 'id');

            //assign task_parent is null to createHierarchicalTree if the task_parent is not exist in parentIds
            foreach ($result as $item) {
                if (!in_array($item->task_parent, $parentIds)) {
                    $originParentIds[$item->id] = $item->task_parent;

                    $item->task_parent = null;
                }
            }

            //create hierarchical tree data
            $tree = $this->createHierarchicalTree($result);

            //assign again task_parent for the records that were assigned task_parent is null above
            if (is_array($tree)) {
                foreach ($tree as $node) {
                    if (isset($originParentIds[$node->id])) {
                        $node->task_parent = $originParentIds[$node->id];
                    }
                }
            }
        } else {
            //create hierarchical tree data
            $tree = $this->createHierarchicalTree($result);
        }

        return $tree;
    }

    private function recursiveSql($sqlJoinRaw, $stringIds, $requestDatas, $isOnlySearchIds, $stringSearchIds = null)
    {
        //get list tasks by list parent id
        $sql = "";
        $sql .= "WITH RECURSIVE parent AS (";

        $sql .= "SELECT";
        $sql .= " tasks.id, tasks.name, tasks.sticker_id, tasks.task_parent,";
        $sql .= " tasks.priority, tasks.weight,";
        $sql .= " tasks.department_id, tasks.user_id, tasks.progress, tasks.quality,";
        $sql .= " tasks.deadline, tasks.status";
        $sql .= " FROM tasks";
        $sql .= " WHERE tasks.id IN (".$stringIds.")";
        $sql .= " AND tasks.deleted_at is null";

        $sql .= " UNION ";

        $sql .= " SELECT";
        $sql .= " child.id, child.name, child.sticker_id, child.task_parent,";
        $sql .= " child.priority, child.weight,";
        $sql .= " child.department_id, child.user_id, child.progress, child.quality,";
        $sql .= " child.deadline, child.status";
        $sql .= " FROM tasks child";
        $sql .= $sqlJoinRaw;
        $sql .= " WHERE child.deleted_at is null";

        $sql .= ")";

        $sql .= "SELECT";
        $sql .= " parent.id, parent.name, parent.sticker_id, parent.task_parent,";
        $sql .= " coalesce(nullif(pj.project_ids, '{null}'), null) as project_id,";
        $sql .= " parent.priority, parent.weight, parent.department_id,";
        $sql .= " parent.user_id, parent.progress, parent.quality, parent.deadline, parent.status,";
        $sql .= " to_char(min(tt.start_time), 'DD/MM/YYYY') as start_time,";
        $sql .= " to_char(max(tt.end_time), 'DD/MM/YYYY') as end_time,";
        $sql .= " coalesce(sum(tt.estimate_time), 0) as total_estimate_time,";
        $sql .= " coalesce(sum(tt.time_spent), 0) as total_time_spent";
        $sql .= " from parent";

        $join = " left join (";
        $searchProject = false;
        if (isset($requestDatas['project_id']) && count($requestDatas['project_id']) > 0) {
            $join = " inner join (";
            $searchProject = true;
        }
        //left join task_projects table
        $sql .= $join;
        $sql .= " select task_id, array_agg(project_id) as project_ids";
        $sql .= " from task_projects";
        $sql .= " where deleted_at is null";
        if ($searchProject) {
            $stringSearchProjectIds = implode(',', array_filter($requestDatas['project_id']));

            $sql .= " and task_projects.task_id in (";
            $sql .= " select task_id from task_projects";
            $sql .= " where deleted_at is null and (";

            if (!empty($stringSearchProjectIds)) {
                $sql .= " project_id in (".$stringSearchProjectIds.")";
            }

            if (in_array(0, $requestDatas['project_id'])) {
                if (!empty($stringSearchProjectIds)) {
                    $sql .= " or task_projects.project_id is null";
                } else {
                    $sql .= " task_projects.project_id is null";
                }
            }

            $sql .= " ) )";
        }
        $sql .= " group by task_id";
        $sql .= " ) pj on pj.task_id = parent.id";

        //left/inner join task_timings table
        $join = " inner join (";
        $searchTime = false;
        if (!empty($requestDatas['start_time']) && !empty($requestDatas['end_time'])) {
            $join = " left join (";
            $searchTime = true;
        }
        $sql .= $join;
        $sql .= " select task_id, min(work_date) as start_time, max(work_date) as end_time,";
        $sql .= " sum(estimate_time) as estimate_time, sum(time_spent) as time_spent";
        $sql .= " from task_timings";
        $sql .= " where deleted_at is null";
        if ($searchTime) {
            $sql .= " and work_date >= '".Carbon::create($requestDatas['start_time'])->format('Y/m/d')."'";

            $sql .= " and work_date <= '".Carbon::create($requestDatas['end_time'])->format('Y/m/d')."'";
        }
        $sql .= " group by task_id";
        $sql .= " ) tt on tt.task_id = parent.id";

        if ($stringSearchIds && !$isOnlySearchIds) {
            $stringSearchIds = implode(',', $requestDatas['ids']);

            $sql .= " where parent.id in (";
            $sql .= " WITH RECURSIVE sub_parent AS (";
            $sql .= " SELECT";
            $sql .= " parent.id";
            $sql .= " FROM parent";
            $sql .= " WHERE parent.id in (".$stringSearchIds.")";
            $sql .= " union";
            $sql .= " SELECT";
            $sql .= " sub_child.id";
            $sql .= " FROM parent sub_child";
            $sql .= " JOIN sub_parent sub_parent on sub_parent.id = sub_child.task_parent )";
            $sql .= " SELECT";
            $sql .= " id";
            $sql .= " FROM sub_parent";
            $sql .= " )";
        }

        $sql .= " group by parent.id, parent.name, pj.project_ids, parent.sticker_id, parent.task_parent,";
        $sql .= " parent.priority, parent.weight, parent.department_id, parent.user_id, parent.progress, parent.quality,";
        $sql .= " parent.deadline, parent.status";

        $task = new Task();
        return DB::connection($task->getConnectionName())->select($sql);
    }

    private function fullRecursiveSql($sqlJoinRaw, $stringIds)
    {
        //get list tasks by list parent id
        $sql = "";
        $sql .= "WITH RECURSIVE parent AS (";

        $sql .= "SELECT";
        $sql .= " tasks.id, tasks.name, tasks.sticker_id, tasks.task_parent,";
        $sql .= " tasks.priority, tasks.weight,";
        $sql .= " tasks.department_id, tasks.user_id, tasks.progress,";
        $sql .= " tasks.status, tasks.description";
        $sql .= " FROM tasks";
        $sql .= " WHERE tasks.id IN (".$stringIds.")";
        $sql .= " AND tasks.deleted_at is null";

        $sql .= " UNION ";

        $sql .= " SELECT";
        $sql .= " child.id, child.name, child.sticker_id, child.task_parent,";
        $sql .= " child.priority, child.weight,";
        $sql .= " child.department_id, child.user_id, child.progress,";
        $sql .= " child.status, child.description";
        $sql .= " FROM tasks child";
        $sql .= $sqlJoinRaw;
        $sql .= " WHERE child.deleted_at is null";

        $sql .= ")";

        $sql .= "SELECT";
        $sql .= " parent.id, parent.name, parent.sticker_id, parent.task_parent,";
        $sql .= " parent.priority, parent.weight, parent.department_id,";
        $sql .= " parent.user_id, parent.progress, parent.status,";
        $sql .= " parent.description";
        $sql .= " FROM parent";

        $task = new Task();
        $data = DB::connection($task->getConnectionName())->select($sql);
        return $data;
    }

    private function fullRecursiveSqlWithIdParentColumns($sqlJoinRaw, $stringIds)
    {
        //get list tasks id by list parent id
        $sql = "";
        $sql .= "WITH RECURSIVE parent AS (";

        $sql .= "SELECT";
        $sql .= " tasks.id";
        $sql .= " FROM tasks";
        $sql .= " WHERE tasks.id IN (".$stringIds.")";
        $sql .= " AND tasks.deleted_at is null";

        $sql .= " UNION ";

        $sql .= " SELECT";
        $sql .= " child.id";
        $sql .= " FROM tasks child";
        $sql .= $sqlJoinRaw;
        $sql .= " WHERE child.deleted_at is null";

        $sql .= ")";

        $sql .= "SELECT";
        $sql .= " parent.id";
        $sql .= " FROM parent";

        $task = new Task();
        $data = DB::connection($task->getConnectionName())->select($sql);
        return $data;
    }

    private function removeRecordNotInTree($tree)
    {
        $idLists = array_column($tree, 'id'); // Get the array of id values
        $single = [];

        foreach ($tree as $key => $value) {
            if (!in_array($value->task_parent, $idLists) && $value->task_parent != null) {
                $single[] = $tree[$key];
                unset($tree[$key]); // Remove the element with id 164
                break; // Exit the loop since the element has been removed
            }
        }

        return [
            'tree' => $tree,
            'single' => $single
        ];
    }

    private function createHierarchicalTree($tree, $root = 0)
    {
        $return = array();
        foreach ($tree as $child => $parent) {
            if ($parent->task_parent == $root) {
                if (isset($tree[$child]->id) === true) {
                    $parent->grandchildren = $this->createHierarchicalTree($tree, $tree[$child]->id);
                }

                unset($tree[$child]);

                $return[] = $parent;
            }
        }

        return empty($return) ? null : $return;
    }

    /** Append SQL if json is requested
     *
     * @param  $requestDatas
     * @param  $query
     * @return $addedQuery
    */
    private function addSqlWithSorting($requestDatas, $query)
    {
        $addedQuery = $query;

        //Change the SQL according to the requested search conditions
        if (!empty($requestDatas['name'])) {
            $name = mb_strtolower(urldecode($requestDatas['name']), 'UTF-8');

            if (strpos($name, ',') !== false) {
                $nameArray = explode(',', $name);
            } else {
                $nameArray = [$name];
            }

            $addedQuery = $addedQuery->where(function ($query) use ($nameArray) {
                foreach ($nameArray as $name) {
                    if ($name) {
                        $query->orWhere(
                            DB::raw('lower(tasks.name)'),
                            'LIKE',
                            '%'.$name.'%'
                        );
                    }
                }
            });
        }

        if (isset($requestDatas['department_id']) && count($requestDatas['department_id']) > 0) {
            $departmentIds = array_filter($requestDatas['department_id']);

            $addedQuery = $addedQuery->where(function ($groupQuery) use ($departmentIds, $requestDatas) {
                if (!empty($departmentIds)) {
                    $groupQuery->whereIn('tasks.department_id', $departmentIds);
                }

                if (in_array(0, $requestDatas['department_id'])) {
                    $groupQuery->orWhereNull('tasks.department_id');
                }
            });
        }

        if (isset($requestDatas['user_id']) && count($requestDatas['user_id']) > 0) {
            $userIds = array_filter($requestDatas['user_id']);

            $addedQuery = $addedQuery->where(function ($groupQuery) use ($userIds, $requestDatas) {
                if (!empty($userIds)) {
                    $groupQuery->whereIn('tasks.user_id', $userIds);
                }

                if (in_array(0, $requestDatas['user_id'])) {
                    $groupQuery->orWhereNull('tasks.user_id');
                }
            });
        }

        if (isset($requestDatas['status']) && count($requestDatas['status']) > 0) {
            $addedQuery = $addedQuery->whereIn('tasks.status', $requestDatas['status']);
        }

        return $addedQuery;
    }

    /** Check relation tasks
     *
     * @param  $taskParent id's parent
     * @param  $id id will be updating
     * @return boolean
    */
    private function checkRelationTask($taskParent, $id)
    {
        //this record will be parent of the record will be updating
        $task = Task::where('id', $taskParent)->first();

        //check if the record will be updating that is parent of above record or not
        if ($task->task_parent == $id) {
            return false;
        }

        //check if the record wii be updating that is parent itself
        if ($taskParent == $id) {
            return false;
        }

        //the record can not be assign task_parent is ít child (note: the record must have task_parent is null)
        //example : 1->2->3->4, the record can not assign 2-3-4 as its parent
        $parent = Task::select('id')
                    ->where('id', $id)
                    ->whereNull('task_parent')
                    ->first();

        if ($parent) {
            $sql = "";
            $sql .= "WITH RECURSIVE parent AS (";

            $sql .= "SELECT";
            $sql .= " tasks.id, tasks.name, tasks.task_parent";
            $sql .= " FROM tasks";
            $sql .= " WHERE tasks.id = ".$id;
            $sql .= " AND tasks.deleted_at is null";

            $sql .= " UNION ";

            $sql .= " SELECT";
            $sql .= " child.id, child.name, child.task_parent";
            $sql .= " FROM tasks child";
            $sql .= " JOIN parent parent ON parent.id = child.task_parent";
            $sql .= " WHERE child.deleted_at is null";

            $sql .= ")";

            $sql .= "SELECT * FROM parent WHERE parent.id = ".$taskParent;

            $model = new Task();
            $data = DB::connection($model->getConnectionName())->select($sql);

            if (count($data) > 0) {
                return false;
            }
        }

        return true;
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

    public function syncData()
    {
        try {
            // $data = Task::get()->toArray();

            // //start transaction
            // DB::beginTransaction();

            // foreach (array_chunk($data, 1000) as $dataChunk) {
            //     $insertableArray = [];
            //     foreach ($dataChunk as $value) {
            //         $insertableArray[] = [
            //             'id' => $value['id'],
            //             'code' => $value['task_code'],
            //             'name' => $value['task_name'],

            //             'start_time' => !$value['start_time'] ? null :
            //                     Carbon::create($value['start_time'])->format('Y/m/d'),

            //             'end_time' => !$value['end_time'] ? null :
            //                     Carbon::create($value['end_time'])->format('Y/m/d H:i:s'),

            //             'time' => $value['time'],
            //             'description' => $value['description'],
            //             'priority' => $value['task_priority'],
            //             'sticker_id' => $value['task_sticker'],
            //             'department_id' => $value['task_department'],
            //             'weight' => $value['weight'],
            //             'project_id' => $value['project_id'],
            //             'task_parent' => $value['task_parent'],
            //             'user_id' => $value['task_performer'],

            //             'created_at' => Carbon::create($value['created_at'])->format('Y/m/d H:i:s'),
            //             'updated_at' => Carbon::create($value['updated_at'])->format('Y/m/d H:i:s'),

            //             'status' => $value['status'],

            //             'real_start_time' => !$value['real_start_time'] ? null :
            //                 Carbon::create($value['real_start_time'])->format('Y/m/d H:i:s'),

            //             'real_end_time' => !$value['real_end_time'] ? null :
            //                 Carbon::create($value['real_end_time'])->format('Y/m/d H:i:s'),

            //             'time_pause' => $value['time_pause'],
            //             'real_time' => $value['real_time'],

            //             'deleted_at' => !$value['deleted_at'] ? null :
            //                 Carbon::create($value['deleted_at'])->format('Y/m/d H:i:s'),

            //             'deleted_by' => $value['deleted_by'],
            //             'progress' => $value['progress'],
            //         ];
            //     }
            //     DB::table('task_backup')->insert($insertableArray);
            // }

            // DB::commit();

            $sql = "";
            $sql .= "WITH RECURSIVE parent AS (";

            $sql .= "SELECT";
            $sql .= " tasks.id, tasks.name, tasks.sticker_id, tasks.task_parent,";
            $sql .= " tasks.priority, to_char(tasks.start_time, 'YYYY/MM/DD') start_time,";
            $sql .= " tasks.time, tasks.weight, tasks.department_id, tasks.user_id, tasks.progress,";
            $sql .= " tasks.status";
            $sql .= " FROM tasks";
            $sql .= " WHERE tasks.task_parent is null";
            $sql .= " AND tasks.deleted_at is null";

            $sql .= " UNION ";

            $sql .= " SELECT";
            $sql .= " child.id, child.name, child.sticker_id, child.task_parent,";
            $sql .= " child.priority, to_char(child.start_time, 'YYYY/MM/DD') start_time,";
            $sql .= " child.time, child.weight, child.department_id, child.user_id, child.progress,";
            $sql .= " child.status";
            $sql .= " FROM tasks child";
            $sql .= " JOIN parent parent ON parent.id = child.task_parent";
            $sql .= " WHERE child.deleted_at is null";

            $sql .= ")";

            $sql .= "SELECT * FROM parent order by id asc";

            $data = DB::select($sql);

            $insertableArray = [];
            foreach ($data as $value) {
                if (!$value->task_parent) {
                    $insertableArray[] = $value->id;
                }
            }

            foreach ($insertableArray as $value1) {
                $this->insertRootParent($value1);
            }

            return 1;
        } catch (\Throwable $th) {
            //throw $th;
            dd($th->getMessage());
        }
    }

    private function insertRootParent($id)
    {
        $sql = "";
        $sql .= "WITH RECURSIVE parent AS (";

        $sql .= "SELECT";
        $sql .= " tasks.id, tasks.name, tasks.sticker_id, tasks.task_parent,";
        $sql .= " tasks.priority, to_char(tasks.start_time, 'YYYY/MM/DD') start_time,";
        $sql .= " tasks.time, tasks.weight, tasks.department_id, tasks.user_id, tasks.progress,";
        $sql .= " tasks.status";
        $sql .= " FROM tasks";
        $sql .= " WHERE tasks.id = ".$id;
        $sql .= " AND tasks.deleted_at is null";

        $sql .= " UNION ";

        $sql .= " SELECT";
        $sql .= " child.id, child.name, child.sticker_id, child.task_parent,";
        $sql .= " child.priority, to_char(child.start_time, 'YYYY/MM/DD') start_time,";
        $sql .= " child.time, child.weight, child.department_id, child.user_id, child.progress,";
        $sql .= " child.status";
        $sql .= " FROM tasks child";
        $sql .= " JOIN parent parent ON parent.id = child.task_parent";
        $sql .= " WHERE child.deleted_at is null";

        $sql .= ")";

        $sql .= "SELECT * FROM parent order by id asc";

        $data = DB::select($sql);

        //start transaction
        DB::beginTransaction();
        foreach ($data as $value) {
            if ($value->task_parent) {
                DB::table('tasks')
                    ->where('id', $value->id)
                    ->update(['root_parent' => $id]);
            }
        }
        DB::commit();
    }

    public function insertTaskIdToTaskTiming()
    {
        try {
            $sql = " select";
            $sql .= " tasks.id, tasks.start_time, tasks.end_time, tasks.time, tasks.real_time";
            $sql .= " from tasks order by id asc";

            $tasks = DB::select($sql);

            //start transaction
            DB::beginTransaction();

            foreach (array_chunk($tasks, 1000) as $taskChunk) {
                $insertableArray = [];

                foreach ($taskChunk as $task) {
                    if ($task->start_time && $task->end_time) {
                        $period = CarbonPeriod::create(
                            Carbon::create($task->start_time)->format('Y-m-d'),
                            Carbon::create($task->end_time)->format('Y-m-d')
                        );

                        foreach ($period->toArray() as $key => $item) {
                            if ($key < 1) {
                                $insertableArray[] = [
                                    'task_id' => $task->id,
                                    'estimate_time' => $task->time,
                                    'time_spent' => $task->real_time,
                                    'work_date' => $item->format('Y-m-d')
                                ];
                            } else {
                                $insertableArray[] = [
                                    'task_id' => $task->id,
                                    'estimate_time' => null,
                                    'time_spent' => null,
                                    'work_date' => $item->format('Y-m-d')
                                ];
                            }
                        }
                    } elseif ($task->start_time && !$task->end_time) {
                        $insertableArray[] = [
                            'task_id' => $task->id,
                            'estimate_time' => $task->time,
                            'time_spent' => $task->real_time,
                            'work_date' => Carbon::create($task->start_time)->format('Y-m-d')
                        ];
                    } elseif (!$task->start_time && $task->end_time) {
                        $insertableArray[] = [
                            'task_id' => $task->id,
                            'estimate_time' => $task->time,
                            'time_spent' => $task->real_time,
                            'work_date' => Carbon::create($task->end_time)->format('Y-m-d')
                        ];
                    } else {
                        $insertableArray[] = [
                            'task_id' => $task->id,
                            'estimate_time' => $task->time,
                            'time_spent' => $task->real_time,
                            'work_date' => null
                        ];
                    }
                }
                // dd($insertableArray);
                DB::table('task_timings')->insert($insertableArray);
            }

            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            dd($th->getMessage());
        }
    }

    public function getTaskListGantt(Request $request)
    {
        try {
            ini_set('memory_limit', '2048M');
            $requestDatas = $request->all();

            if ($requestDatas == []) {
                return response()->json(
                    [
                        'status' => Response::HTTP_NOT_FOUND,
                        'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }
            if (!isset($requestDatas['project_id']) || $requestDatas['project_id'] == null) {
                return response()->json(
                    [
                        'status' => Response::HTTP_NOT_FOUND,
                        'errors' => 'Dự án không được để trống'
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $tasks = Task::leftJoin('task_projects', function ($join) use ($requestDatas) {
                $join->on('task_projects.task_id', '=', 'tasks.id')->whereNull('task_projects.deleted_at');

                // if (isset($requestDatas['project_id']) && $requestDatas['project_id'] != null) {
                //     $join->where(function ($groupQuery) use ($requestDatas) {
                //         if (!is_null($requestDatas['project_id'])) {
                //             $groupQuery->where('task_projects.project_id', $requestDatas['project_id']);
                //         } else {
                //             $groupQuery->orWhereNull('task_projects.project_id');
                //         }
                //     });
                // }
            })
            ->join('task_timings', function ($join) use ($requestDatas) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');
            })
            ->leftJoin('task_users', function ($join) use ($requestDatas) {
                $join->on('tasks.id', '=', 'task_users.task_id')->whereNull('task_users.deleted_at');
            })
            // ->where('task_projects.project_id', $requestDatas['project_id'])
            // ->select('tasks.id','tasks.name','tasks.task_parent','tasks.start_time','tasks.end_time','tasks.progress')
            // ->whereNotNull('tasks.name')
            ->select(
                'tasks.id',
                'tasks.name',
                'tasks.description',
                'tasks.task_parent',
                'tasks.progress',
                'tasks.user_id',
                'tasks.department_id',
                'tasks.status',
                'tasks.priority',
                'tasks.sticker_id',
                'tasks.weight',
                'tasks.deadline',
                DB::raw('MIN(task_timings.work_date) as start_time'),
                DB::raw('MAX(task_timings.work_date) as end_time')
            );

            if (isset($requestDatas['project_id']) && $requestDatas['project_id'] != null) {
                $tasks->where('task_projects.project_id', $requestDatas['project_id']);
            }
            if (isset($requestDatas['user_id']) && $requestDatas['user_id'] != []) {
                $tasks->whereIn('tasks.user_id', $requestDatas['user_id']);
            }
            if (isset($requestDatas['department_id']) && $requestDatas['department_id'] != []) {
                $tasks->whereIn('tasks.department_id', $requestDatas['department_id']);
            }
            if (isset($requestDatas['name']) && $requestDatas['name'] != '') {
                $tasks->where(
                    DB::raw('lower(tasks.name)'),
                    'LIKE',
                    '%'.mb_strtolower(urldecode($requestDatas['name']), 'UTF-8').'%'
                );
            }
            $tasks = $tasks->groupBy('tasks.id', 'tasks.name', 'tasks.description', 'tasks.task_parent', 'tasks.progress', 'tasks.user_id', 'tasks.department_id', 'tasks.status', 'tasks.priority', 'tasks.sticker_id','tasks.weight','tasks.deadline')
            ->get();
            //no search results
            if (!$tasks) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }
            $departments = config('const.departments');
            $status = config('const.status');
            $tasks = $tasks->map(function ($item) use ($departments, $status) {
                if ($item->department_id != null) {
                    $item->department_name = $departments[$item->department_id];
                }
                // if ($item->status != null) {
                //     $item->status_name = $status[$item->status];
                // }
                foreach ($status as $val) {
                    if (isset($item->status) && $val['value'] == $item->status) {
                        $item->status_name = $val['label'];
                    }
                }
                return $item;
            });
            $sortedTasks = $this->sortTasks($tasks);
            $data = [];
            $link = [];
            foreach ($sortedTasks as $key => $value) {
                $data[$key]['id'] = $value['id'];
                $data[$key]['text'] = $value['name'];
                $data[$key]['description'] = $value['description'];
                $data[$key]['progress'] = $value['progress'] / 100;
                $data[$key]['parent'] = $value['task_parent'];
                $data[$key]['owner'] = [
                    'resource_id' => $value['user_id'],
                ];
                $data[$key]['open'] = false;
                $data[$key]['department_id'] = $value['department_id'];
                $data[$key]['department_name'] = $value['department_name'];
                $data[$key]['status_name'] = $value['status_name'];
                $data[$key]['status'] = $value['status'];
                $data[$key]['priority'] = $value['priority'];
                $data[$key]['sticker_id'] = $value['sticker_id'];
                $data[$key]['user_id'] = $value['user_id'];
                $data[$key]['deadline'] = $value['deadline'];

                $start_date = isset($value['start_date_max']) ? $value['start_date_max'] : $value['start_time'];
                $end_date = isset($value['end_date_max']) ? $value['end_date_max'] : $value['end_time'];

                $data[$key]['start_date'] = Carbon::parse($start_date)->format('Y-m-d');
                $data[$key]['end_date'] = Carbon::parse($end_date)->format('Y-m-d');

                // $data[$key]['type'] = $value['task_parent'] == null ? 'project' : 'task';

                if ($value['task_parent'] != null) {
                    $link[] = [
                        'id' => $key,
                        'source' => $value['task_parent'],
                        'target' => $value['id'],
                        'type' => '0'
                    ];
                }
            }

            $datas = [
                'data' => $data,
                'links' => $link,
            ];

            return response()->json($datas);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function sortTasks($tasks) {
        $taskById = $tasks->keyBy('id')->all();
        $childTasks = [];
        $datesByParent = [];

        foreach ($tasks as $task) {
            $childTasks[$task['task_parent']][] = $task['id'];
        }

        return $this->buildHierarchyAndUpdateDates($taskById, $childTasks, null);
    }

    private function buildHierarchyAndUpdateDates(&$taskById, &$childTasks, $parentId) {
        $sortedTasks = [];
        $childStartDates = [];
        $childEndDates = [];

        if (isset($childTasks[$parentId])) {
            foreach ($childTasks[$parentId] as $childId) {
                $child = $taskById[$childId];

                if (!isset($taskById[$parentId])) {
                    $taskById[$parentId] = ['start_date_max' => null, 'end_date_max' => null];
                }

                $childStartDate = isset($child['start_time']) ? $child['start_time'] : null;
                $childEndDate = isset($child['end_time']) ? $child['end_time'] : null;

                $childSorted = $this->buildHierarchyAndUpdateDates($taskById, $childTasks, $childId);

                $childStartDates[] = isset($child['start_date_max']) ? $child['start_date_max'] : $childStartDate;
                $childEndDates[] = isset($child['end_date_max']) ? $child['end_date_max'] : $childEndDate;

                $sortedTasks[] = $child;
                $sortedTasks = array_merge($sortedTasks, $childSorted);
            }

            $taskById[$parentId]['start_date_max'] = !empty($childStartDates) ? min($childStartDates) : null;
            $taskById[$parentId]['end_date_max'] = !empty($childEndDates) ? max($childEndDates) : null;
        }

        return $sortedTasks;
    }

    public function getSelectboxesGantt()
    {
        try {
            //list users by department with job
            // $departments = CommonController::getDepartmentsJob();

            $departments = config('const.departments');
            $departments = array_map(function ($id, $name) {
                return ['key' => $id, 'label' => $name];
            }, array_keys($departments), $departments);

            $dpIds = array_map(function ($department) {
                return $department['key'];
            }, $departments);


            $status = config('const.status');
            $status = array_map(function ($item) {
                return ['key' => $item['value'], 'label' => $item['label']];
            }, $status);

            //stickers
            $stickers = Sticker::select(
                'id as id',
                'id as key',
                'name as label',
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
            )
            ->get();

            $projects = Project::select('id as key', 'name as label')->orderBy('ordinal_number', 'asc')->get();
            $priorities = Priority::select('id as id','id as key', 'label')->get();
            $users = User::select('id', 'fullname as text', 'department_id as parent' )
                        ->where('user_status', 1)->get();

            $data = [
                'projects' => $projects,
                'users' => $users,
                'status' => $status,
                'departments' => $departments,
                'priorities' => $priorities,
                'stickers' => $stickers,
            ];

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function storeGantt(TaskRegisterRequest $request)
    {
        $requestDatas = $request->all();
        $model = new Task();
        $connectionName = $model->getConnectionName();

        try {
            //find root_parent by task_parent
            $root_parent = null;
            $task_parent = null;
            if (!empty($requestDatas['task_parent'])) {
                $task_parent = $requestDatas['task_parent'];
                $root_parent = $this->getRootParentId($task_parent);
            }

            $name = isset($requestDatas['name']) ? $requestDatas['name'] : null;
            //init saveData
            $saveData = [
                'name' => $name,
                'code' => $this->generateCodeFromName($name),
                'description' => $requestDatas['description'],
                'task_parent' => $task_parent,
                'root_parent' => $root_parent,
            ];

            //create save data when type is child
            if ($requestDatas['type'] == 'child') {
                $saveData = array_merge($saveData, [
                    'weight' => $requestDatas['weight'],
                    'priority' => $requestDatas['priority'],
                    'sticker_id' => $requestDatas['sticker_id'],
                    'department_id' => $requestDatas['department_id'],
                    'status' => $requestDatas['status'],
                    'user_id' => $requestDatas['user_id']
                ]);
            }


            //start transaction control
            DB::connection($connectionName)->beginTransaction();

            $task = Task::create($saveData);

            //insert task_projects table
            if (isset($requestDatas['project_ids']) && count($requestDatas['project_ids']) > 0) {
                foreach ($requestDatas['project_ids'] as $value) {
                    TaskProject::create([
                        'task_id' => $task->id,
                        'project_id' => $value,
                    ]);
                }
            }

            $this->insertWorkDateTaskTiming($task->id, [
                'start_time' => isset($requestDatas['start_time']) ? $requestDatas['start_time'] : "",
                'end_time' => isset($requestDatas['end_time']) ? $requestDatas['end_time'] : "",
            ]);

            //clone the task if it has many childs
            if ($requestDatas['clone_id']) {
                $cloneId = $requestDatas['clone_id'];

                $sqlJoinRaw = " JOIN parent parent ON parent.id = child.task_parent";

                $childs = $this->fullRecursiveSql($sqlJoinRaw, $cloneId);

                $oldParentIds = [];
                foreach ($childs as $child) {
                    $oldParentIds[$cloneId] = $task->id;

                    if ($child->id == $cloneId) {
                        continue;
                    }

                    $newChildId = Task::create([
                        'name' => $child->name,
                        'code' => $this->generateCodeFromName($child->name),
                        'description' => $child->description,
                        'priority' => $child->priority,
                        'sticker_id' => $child->sticker_id,
                        'department_id' => $child->department_id,
                        'weight' => $child->weight,
                        'project_id' => $task->project_id,
                        'task_parent' => $oldParentIds[$child->task_parent],
                        'user_id' => $child->user_id,
                        'status' => $child->status,
                        'root_parent' => $root_parent ? $root_parent : $task->id,
                        'progress' => $child->progress
                    ]);

                    $taskTimings = TaskTiming::select('id', 'work_date', 'estimate_time', 'time_spent')
                                    ->where(function ($query) use ($requestDatas) {
                                        if (!empty($requestDatas['start_time'])) {
                                            $query->whereDate(
                                                'work_date',
                                                '>=',
                                                Carbon::create($requestDatas['start_time'])->format('Y/m/d')
                                            );
                                        }
                                        if (!empty($requestDatas['end_time'])) {
                                            $query->whereDate(
                                                'work_date',
                                                '<=',
                                                Carbon::create($requestDatas['end_time'])->format('Y/m/d')
                                            );
                                        }
                                    })
                                    ->where('task_id', $child->id)
                                    ->where('type', 0)
                                    ->get();

                    foreach ($taskTimings as $taskTiming) {
                        $date = $taskTiming->work_date ? Carbon::createFromFormat('d/m/Y', $taskTiming->work_date)
                            : null;
                        //insert to task_timings table
                        TaskTiming::create([
                            'task_id' => $newChildId->id,
                            'work_date' => $date ? $date->format('Y/m/d') : null,
                            'type' => 0
                        ]);
                    }

                    //make a copy for task_projects tables
                    $taskProjects = TaskProject::where('task_id', $child->id)->get();
                    foreach ($taskProjects as $item1) {
                        TaskProject::create([
                            'task_id' =>  $newChildId->id,
                            'project_id' => $item1->project_id,
                        ]);
                    }

                    $oldParentIds[$child->id] = $newChildId->id;
                }
            }

            DB::connection($connectionName)->commit();
        } catch (Exception $e) {
            DB::connection($connectionName)->rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateGantt(TaskEditRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            Task::performTransaction(function ($model) use ($requestDatas) {
                $attributesChanged = $this->doUpdate($requestDatas['id'], $requestDatas);

                if (is_array($requestDatas['id_list']) && count($requestDatas['id_list']) > 0) {
                    $index = array_search($requestDatas['id'], $requestDatas['id_list']);
                    if ($index !== false) {
                        array_splice($requestDatas['id_list'], $index, 1);
                    }

                    foreach ($requestDatas['id_list'] as $id) {
                        $this->doMultipleUpdate($id, $requestDatas, $attributesChanged);
                    }
                }
            });
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'errors' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
        }
    }
    public function destroyGantt(Request $request)
    {
        $requestDatas = $request->all();

        try {
            // Retrieve the resources with the given IDs
            $resourcesToDelete = Task::whereIn('id', $requestDatas['ids'])->get();

            // Verify that the number of retrieved resources matches the number of requested IDs
            if ($resourcesToDelete->count() !== count($requestDatas['ids'])) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $model = new Task();
            $connectionName = $model->getConnectionName();
            //start transaction control
            DB::connection($connectionName)->beginTransaction();

            //delete tasks with parent and its childs
            if ($requestDatas['mode'] == 2) {
                // Delete tasks with the given IDs
                Task::whereIn('id', $requestDatas['ids'])->delete();
                // Delete task files with the given IDs
                TaskFile::whereIn('task_files.task_id', $requestDatas['ids'])->delete();
                // Delete task_timings with the given IDs
                TaskTiming::whereIn("task_timings.task_id", $requestDatas['ids'])->delete();
                //delete task projects
                TaskProject::whereIn("task_projects.task_id", $requestDatas['ids'])->delete();
                // Delete task timing projects
                TaskTimingProject::whereIn('task_id', $requestDatas['ids'])->delete();
            } elseif ($requestDatas['mode'] == 1) {
            //delete tasks with only parent and keep its childs
                foreach ($requestDatas['ids'] as $id) {
                    $task = Task::findOrFail($id);

                    $taskParent = $task->task_parent;
                    $rootParent = $task->root_parent;

                    if ($task->delete()) {
                        //delete task timing projects
                        TaskTimingProject::where('task_id', $id)->delete();
                    }

                    //update task's child to greater parent
                    $taskChild = Task::select('id')->where('task_parent', $id)->get();

                    if (count($taskChild) > 0) {
                        foreach ($taskChild as $value) {
                            $child = Task::find($value->id);
                            if ($child) {
                                $child->task_parent = $taskParent;
                                $child->root_parent = $rootParent;
                                $child->save();

                                $this->updateRootParent($rootParent, $child->id);
                            }
                        }
                    }
                }
            }

            DB::connection($connectionName)->commit();
        } catch (ExclusiveLockException $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'errors' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            DB::connection($connectionName)->rollBack();
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function changeParentGantt(Request $request)
    {
        $requestDatas = $request->all();

        try {
                $task = Task::findOrFail($requestDatas['id']);
                //find root_parent by task_parent
                $root_parent = null;
                $task_parent = null;
                if (!empty($requestDatas['task_parent'])) {
                    $task_parent = $requestDatas['task_parent'];

                    //check relation tasks
                    $check = $this->checkRelationTask($task_parent, $task->id);
                    if (!$check) {
                        return response()->json(
                            ['status' => Response::HTTP_NOT_FOUND,
                            'errors' => __('MSG-E-012')
                            ],
                            Response::HTTP_NOT_FOUND
                        );
                    }

                    //detect new task_parent value
                    $task->task_parent = $requestDatas['task_parent'];
                    if ($task->isDirty('task_parent')) {
                        $root_parent = $this->getRootParentId($task_parent);
                    }
                }

                //remove task_parent from task's child
                if (is_null($requestDatas['task_parent'])) {
                    $task->task_parent = null;
                    $task->root_parent = null;
                }

                if ($root_parent) {
                    $task->root_parent = $root_parent;
                }

                $task->save();

                // update root_parent for task's child
                $sqlJoinRaw = " JOIN parent parent ON parent.id = child.task_parent";
                $childs = $this->fullRecursiveSqlWithIdParentColumns($sqlJoinRaw, $task->id);

                if (count($childs) > 1) {
                    $ids = [];
                    foreach ($childs as $child) {
                        $ids[] = $child->id;
                    }

                    Task::where('id', '!=', $task->id)->whereIn('id', $ids)
                        ->update(['root_parent' => $root_parent ? $root_parent : $task->id]);
                }
                //end
        } catch (ExclusiveLockException $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'errors' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getStickers(request $request)
    {
        try {

            $data = Sticker::select(
                'id',
                'name',
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
            )
            ->where(function ($query) use ($request) {
                if (!empty($request->department_id)) {
                    $query->where('department_id', $request->department_id);
                }
            })
            ->get();

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
