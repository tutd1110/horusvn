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
use App\Models\User;
use App\Models\Task;
use App\Models\TaskDeadline;
use App\Models\TaskProject;
use App\Models\TaskTiming;
use App\Models\TaskTimingProject;
use App\Models\Sticker;
use App\Models\Priority;
use App\Models\PinTask;
use App\Http\Requests\api\Task\Department\GetTaskListRequest;
use App\Http\Requests\api\Task\Department\TaskRegisterRequest;
use App\Http\Requests\api\Task\Department\GetTaskByIdRequest;
use App\Http\Requests\api\Task\Department\TaskEditRequest;
use App\Http\Requests\api\Task\Department\TaskQuickEditRequest;
use App\Http\Requests\api\Task\Department\TaskDeleteRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Department Task API
 *
 * @group Department Task
 */
class DepartmentTaskController extends Controller
{
    /** Select boxes data
     *
     *
     * @group Department Task
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
            //user info
            $user = Auth()->user();

            //list users by department with job
            $departments = CommonController::getDepartmentsJob();
            $status = config('const.status');
            $taskTimingTypes = config('const.task_timings_type');
            //employees role
            $leaderIdsRole = config('const.employee_id_leader_roles');
            $pmIdsRole = config('const.employee_id_pm_roles');
            $add_permission = config('const.employee_add_permission');

            $projects = Project::select('id', 'name')->orderBy('ordinal_number', 'asc')->get();

            $users = CommonController::getEmployeesWorking();

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
            )
            // ->where('department_id', $user->department_id)
            ->get();

            $priorities = Priority::select('id', 'label')->get();

            $data = [
                'projects' => $projects,
                'users' => $users,
                'status' => $status,
                'task_timing_type' => $taskTimingTypes,
                'departments' => $departments,
                'stickers' => $stickers,
                'priorities' => $priorities,
                'session' => [
                    'id' => $user->id,
                    'department_id' => $user->department_id,
                    'is_authority' => in_array($user->id, $leaderIdsRole) ? true : false,
                    'is_manager' => in_array($user->id, $pmIdsRole) ? true : false,
                    'add_permission' => in_array($user->id, $add_permission) ? true : false
                ],
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
     * @group Department Task
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
            //user info
            $user = Auth()->user();

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
            ->where('department_id', $user->department_id)
            ->get()->toArray();
            //status
            $status = config('const.status');
            $projects = Project::select('id', 'name')->orderBy('ordinal_number', 'asc')->get();
            $users = User::select('id', 'fullname', 'department_id')
                        ->where('position', '!=', 3)
                        ->get();

            $data = [
                'projects' => $projects,
                'users' => $users,
                'status' => $status,
                'departments' => $departments,
                'type_of_task' => $typeOfTask,
                'priorities' => $priorities,
                'stickers' => $stickers,
                'is_manager' => $user->position > 1 || $user->permission > 0 ? true : false
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

    /** Tasks list for department
     *
     *
     * @group Department Tasks
     *
     * @bodyParam project_id bigint optional (Dự án)
     * @bodyParam user_id bigint optional (Người thực hiện)
     * @bodyParam start_time date optional (Ngày bắt đầu)
     * @bodyParam name string optional (Tên dự án)
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
            //On request
            $requestDatas = $request->all();

            $workDatePeriod = $this->getWorkDatePeriod($requestDatas);

            $requestDatas['start_time'] = $workDatePeriod['start_time'];
            $requestDatas['end_time'] = $workDatePeriod['end_time'];

            $subquery = TaskProject::select('task_id', DB::raw('array_agg(project_id) as project_ids'))
            ->whereNull('deleted_at')
            ->whereIn('task_id', function ($query) use ($requestDatas) {
                $query->select('task_id')
                    ->from('task_projects')
                    ->where(function ($query) use ($requestDatas) {
                        if (isset($requestDatas['project_id']) && count($requestDatas['project_id']) > 0) {
                            $query->whereIn('project_id', $requestDatas['project_id'])->whereNull('deleted_at');
                        }
                    });
            })
            ->groupBy('task_id');

            $query = Task::join('task_timings', function ($join) use ($workDatePeriod) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');
            })
            ->when(isset($requestDatas['mode']) && $requestDatas['mode'] === "bug", function ($query) use ($requestDatas) {
                $query->join('task_assignments', function ($join) {
                    $join->on('tasks.id', '=', 'task_assignments.task_id')->whereNull('task_assignments.deleted_at');
                });
            })
            ->when(
                isset($requestDatas['project_id']) && count($requestDatas['project_id']) > 0,
                function ($query) use ($subquery) {
                    $query->joinSub($subquery, 'pj', function ($join) {
                        $join->on('pj.task_id', '=', 'tasks.id');
                    });
                },
                function ($query) use ($subquery) {
                    $query->leftJoinSub($subquery, 'pj', function ($join) {
                        $join->on('pj.task_id', '=', 'tasks.id');
                    });
                }
            )
            ->when(!empty($requestDatas['option']) && $requestDatas['option'] == 7, function ($query) {
                $query->join('pin_tasks', function ($join) {
                    $join->on('tasks.id', '=', 'pin_tasks.task_id')->whereNull('pin_tasks.deleted_at');
                    // ->where('pin_tasks.user_id', Auth()->user()->id);
                    if (Auth()->user()->id != 51) {
                        $join->where('pin_tasks.user_id', Auth()->user()->id);
                    }
                });
            }, function ($query) {
                $query->leftJoin('pin_tasks', function ($join) {
                    $join->on('tasks.id', '=', 'pin_tasks.task_id')->whereNull('pin_tasks.deleted_at');
                    if (Auth()->user()->id != 51) {
                        $join->where('pin_tasks.user_id', Auth()->user()->id);
                    }
                });
            })
            ->selectRaw(DB::raw("
                tasks.id as id,
                tasks.name as name,
                tasks.sticker_id as sticker_id,
                coalesce(nullif(pj.project_ids, '{null}'), null) as project_id,
                tasks.priority as priority,
                tasks.task_parent as task_parent,
                min(task_timings.work_date) as start_work_date,
                max(task_timings.work_date) as end_work_date,
                coalesce(sum(task_timings.estimate_time), 0) as total_estimate_time,
                coalesce(sum(task_timings.time_spent), 0) as total_time_spent,
                tasks.weight as weight,
                tasks.description as description,
                tasks.department_id as department_id,
                tasks.user_id as user_id,
                tasks.progress as progress,
                tasks.deadline as deadline,
                tasks.status as status,
                tasks.quality as quality,
                case when pin_tasks.task_id is not null then 1 else 0 end as is_pinned
                "))
            // ->selectRaw(DB::raw("
            //     (
            //         SELECT COUNT(*)
            //         FROM task_deadlines
            //         LEFT JOIN deadline_modifications ON deadline_modifications.task_deadline_id = task_deadlines.id
            //             AND deadline_modifications.deleted_at IS NULL
            //         WHERE task_deadlines.task_id = tasks.id
            //         AND task_deadlines.deleted_at IS NULL
            //         AND task_deadlines.estimate_date < (
            //             SELECT MAX(work_date)
            //             FROM task_timings
            //             WHERE task_timings.task_id = tasks.id AND task_timings.deleted_at IS NULL
            //         )
            //     ) AS overdue_task,
            //     (
            //         SELECT COUNT(*)
            //         FROM task_deadlines
            //         LEFT JOIN deadline_modifications ON deadline_modifications.task_deadline_id = task_deadlines.id
            //         WHERE task_deadlines.task_id = tasks.id
            //         AND task_deadlines.deleted_at is null
            //         AND deadline_modifications.status = 3
            //         AND task_deadlines.estimate_date < (
            //             SELECT MAX(work_date)
            //             FROM task_timings
            //             WHERE task_timings.task_id = tasks.id AND task_timings.deleted_at IS NULL
            //         )
            //     ) AS none_overdue_task
            // "))
            ->where(function ($query) use ($requestDatas) {
                if (!empty($requestDatas['start_time']) && !empty($requestDatas['end_time'])) {
                    $query->where(function ($query) use ($requestDatas) {
                        $query->whereDate('task_timings.work_date', '>=', $requestDatas['start_time'])
                            ->whereDate('task_timings.work_date', '<=', $requestDatas['end_time']);
                    });
                    if (!empty($requestDatas['is_pin_show']) && $requestDatas['is_pin_show'] == 1) {
                        $query->orWhere(function ($query) use ($requestDatas) {
                            if (!empty($requestDatas['option']) && $requestDatas['option'] != 7) {
                                $query->orWhereNotNull('pin_tasks.task_id');
                            }
                        });
                    }
                }
            });

            //Add SQL according to requested search conditions
            if (!empty($requestDatas)) {
                //get json from request
                $query = $this->addSqlWithSorting($requestDatas, $query);
                if (isset($requestDatas['issue'])) {
                    $query->where('task_timings.type', $requestDatas['issue']);
                }

                if (isset($requestDatas['overdue']) && $requestDatas['overdue'] == 1) {
                    $query->whereExists(function ($subQuery) use ($query) {
                        $subQuery->select(DB::raw(1))
                            ->from('task_deadlines')
                            ->whereColumn('task_deadlines.task_id', 'tasks.id')
                            ->where('task_deadlines.deleted_at', null)
                            ->whereRaw('task_deadlines.estimate_date < (
                                SELECT MAX(work_date)
                                FROM task_timings
                                WHERE task_timings.task_id = tasks.id
                            )');
                    });
                }
                if (isset($requestDatas['overdue']) && $requestDatas['overdue'] == 2) {
                    $query->whereNull('tasks.deadline');
                }
            }
            $total = $query->groupBy('tasks.id', 'pj.project_ids', 'pin_tasks.task_id')->get()->count();

            $column = isset($requestDatas['column']) ? $requestDatas['column'] : null;
            $order = isset($requestDatas['order']) ? $requestDatas['order'] : null;
            if ($column && $order) {
                $column = $requestDatas['column'] === 'work_date' ? 'start_work_date' : $requestDatas['column'];
                // Use dynamic ordering based on the provided column and order parameters.
                $query = $query->orderBy($column, $order);
            } else {
                // Keep the original orderByRaw logic.
                $query = $query->orderByRaw(
                    'is_pinned DESC,
                    min(task_timings.work_date) IS NULL ASC,
                    min(task_timings.work_date) DESC,
                    tasks.created_at DESC'
                );
            }
            
            if ($total/$requestDatas['per_page'] < $requestDatas['current_page']) {
                $requestDatas['current_page'] = ceil($total/$requestDatas['per_page']);
            }
            $tasks = $query->groupBy('tasks.id', 'pj.project_ids', 'pin_tasks.task_id')
                            ->orderByRaw(
                                'is_pinned DESC,
                                min(task_timings.work_date) IS NULL ASC,
                                min(task_timings.work_date) DESC,
                                tasks.created_at DESC'
                            )
                            ->offset(($requestDatas['current_page'] - 1) * $requestDatas['per_page'])
                            ->limit($requestDatas['per_page'])
                            ->get();

            //no search results
            if (count($tasks) === 0) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $data = [
                'items' => $this->transferTasks($tasks),
                'currentPage' => $requestDatas['current_page'],
                'perPage' => $requestDatas['per_page'],
                'totalItems' => $total
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

    public function getTaskInfo(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $workDatePeriod = $this->getWorkDatePeriod($requestDatas);

            $requestDatas['start_time'] = $workDatePeriod['start_time'];
            $requestDatas['end_time'] = $workDatePeriod['end_time'];

            $subquery = TaskProject::select('task_id', DB::raw('array_agg(project_id) as project_ids'))
            ->whereNull('deleted_at')
            ->whereIn('task_id', function ($query) use ($requestDatas) {
                $query->select('task_id')
                    ->from('task_projects')
                    ->where(function ($query) use ($requestDatas) {
                        if (isset($requestDatas['project_id']) && count($requestDatas['project_id']) > 0) {
                            $query->whereIn('project_id', $requestDatas['project_id']);
                        }
                    });
            })
            ->groupBy('task_id');

            $query = Task::join('task_timings', function ($join) use ($workDatePeriod) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');

                if (!empty($workDatePeriod['start_time']) && !empty($workDatePeriod['end_time'])) {
                    $join->whereDate('task_timings.work_date', '>=', $workDatePeriod['start_time'])
                        ->whereDate('task_timings.work_date', '<=', $workDatePeriod['end_time']);
                }
            })
            ->when(
                isset($requestDatas['project_id']) && count($requestDatas['project_id']) > 0,
                function ($query) use ($subquery) {
                    $query->joinSub($subquery, 'pj', function ($join) {
                        $join->on('pj.task_id', '=', 'tasks.id');
                    });
                },
                function ($query) use ($subquery) {
                    $query->leftJoinSub($subquery, 'pj', function ($join) {
                        $join->on('pj.task_id', '=', 'tasks.id');
                    });
                }
            )
            ->selectRaw(DB::raw("
                count(DISTINCT tasks.id) as total_task,
                count(DISTINCT tasks.id) filter (where tasks.status = 0) as total_slow,
                count(DISTINCT tasks.id) filter (where tasks.status = 1) as total_wait,
                count(DISTINCT tasks.id) filter (where tasks.status = 2) as total_processing,
                count(DISTINCT tasks.id) filter (where tasks.status = 3) as total_pause,
                count(DISTINCT tasks.id) filter (where tasks.status = 4) as total_complete,
                count(DISTINCT tasks.id) filter (where tasks.status = 5) as total_wait_fb,
                count(DISTINCT tasks.id) filter (where tasks.status = 6) as total_again,
                coalesce(sum(case when task_timings.task_id IS NOT NULL
                    then task_timings.estimate_time else 0 end), 0) as total_estimate_time,
                coalesce(sum(case when task_timings.task_id IS NOT NULL
                    then task_timings.time_spent else 0 end), 0) as total_time_spent"));

            $weightQuery = Task::query()
                        ->selectRaw(DB::raw("
                            coalesce(sum(tasks.weight) filter (where tasks.status = 4), 0) as total_weight_complete,
                            coalesce(sum(tasks.weight), 0) as total_weight"));
            
            //Add SQL according to requested search conditions
            //On request
            $requestDatas = $request->all();
            if (!empty($requestDatas)) {
                //get json from request
                $query = $this->addSqlWithSorting($requestDatas, $query);
                $weightQuery = $this->addSqlWithSorting($requestDatas, $weightQuery);
            }
            $info = $query->get();
            $weight = $weightQuery->get();

            $info[0]->total_weight_complete = $weight[0]->total_weight_complete;
            $info[0]->total_weight = $weight[0]->total_weight_complete;
            
            return response()->json($info[0]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    private function getWorkDatePeriod($requestDatas)
    {
        $duration = [
            'start_time' => '',
            'end_time' => ''
        ];

        if (!empty($requestDatas['start_time']) && !empty($requestDatas['end_time'])) {
            $duration['start_time'] = Carbon::create($requestDatas['start_time'])->format('Y/m/d');
            $duration['end_time'] = Carbon::create($requestDatas['end_time'])->format('Y/m/d');
        } else {
            if (!empty($requestDatas['option']) && $requestDatas['option'] != 1) {
                switch ($requestDatas['option']) {
                    case 2: //today
                        $duration['start_time'] = Carbon::now()->format('Y/m/d');
                        $duration['end_time'] = Carbon::now()->format('Y/m/d');
    
                        break;
                    case 3: //yesterday
                        $duration['start_time'] = Carbon::yesterday()->format('Y/m/d 00:00:00');
                        $duration['end_time'] = Carbon::yesterday()->format('Y/m/d 23:59:59');
    
                        break;
                    case 4: //this week
                        $duration['start_time'] = Carbon::now()->startOfWeek()->format('Y/m/d');
                        $duration['end_time'] = Carbon::now()->endOfWeek()->format('Y/m/d');
    
                        break;
                    case 5: //last week
                        $duration['start_time'] = Carbon::now()->subWeek()->startOfWeek()->format('Y/m/d');
                        $duration['end_time'] = Carbon::now()->subWeek()->endOfWeek()->format('Y/m/d');
    
                        break;
                    case 8: //this month
                        $duration['start_time'] = Carbon::now()->startOfMonth()->format('Y/m/d');
                        $duration['end_time'] = Carbon::now()->endOfMonth()->format('Y/m/d');
    
                        break;
                    case 9: //last month
                        $duration['start_time'] = Carbon::now()->subMonth()->startOfMonth()->format('Y/m/d');
                        $duration['end_time'] = Carbon::now()->subMonth()->endOfMonth()->format('Y/m/d');
    
                        break;
                    default:
                        # code...
                        break;
                }
            }
        }

        return $duration;
    }

    public function getTaskInfoByEmployee(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $workDatePeriod = $this->getWorkDatePeriod($requestDatas);

            $requestDatas['start_time'] = $workDatePeriod['start_time'];
            $requestDatas['end_time'] = $workDatePeriod['end_time'];

            $query = Task::join('task_timings', function ($join) use ($workDatePeriod) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');

                if (!empty($workDatePeriod['start_time']) && !empty($workDatePeriod['end_time'])) {
                    $join->whereDate('task_timings.work_date', '>=', $workDatePeriod['start_time'])
                        ->whereDate('task_timings.work_date', '<=', $workDatePeriod['end_time']);
                }
            })
            ->leftJoin('users', 'users.id', '=', 'tasks.user_id')
            ->select(DB::raw("
                count(DISTINCT tasks.id) filter (where tasks.status = 1) as total_wait,
                count(DISTINCT tasks.id) filter (where tasks.status = 2 or tasks.status = 6) as total_processing,
                count(DISTINCT tasks.id) filter (where tasks.status = 3) as total_pending,
                count(DISTINCT tasks.id) filter (where tasks.status = 4 or tasks.status = 5) as total_completed,
                count(DISTINCT tasks.id) filter (where tasks.status = 0) as total_overdue,
                count(DISTINCT tasks.id) filter (where tasks.deadline is null) as none_deadline,
                max(tasks.deadline) as final_deadline,
                users.fullname as fullname"))
            ->where('users.user_status', 1);
            
            //Add SQL according to requested search conditions
            //On request
            $requestDatas = $request->all();
            if (!empty($requestDatas)) {
                //get json from request
                $query = $this->addSqlWithSorting($requestDatas, $query);
            }
            $info = $query->groupBy('users.fullname')->get();
            
            return response()->json($info);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    private function transferTasks($tasks)
    {
        $newData = array();

        foreach ($tasks as $value) {
            //format date
            $startWorkDate = $value->start_work_date ? Carbon::create($value->start_work_date)->format("d/m/Y") : null;
            $endWorkDate = $value->end_work_date ? Carbon::create($value->end_work_date)->format("d/m/Y") : null;

            //Push element onto the newData array
            array_push($newData, [
                "id" => $value->id,
                "name" => $value->name,
                "project_id" => $value->project_id,
                "sticker_id" => $value->sticker_id,
                "task_parent" => $value->task_parent,
                "priority" => $value->priority,
                "start_time" => $startWorkDate,
                "end_time" => $endWorkDate,
                "total_estimate_time" => $value->total_estimate_time,
                "total_time_spent" => $value->total_time_spent,
                "weight" => $value->weight,
                "description" => $value->description,
                "department_id" => $value->department_id,
                "user_id" => $value->user_id,
                "progress" => $value->progress,
                "deadline" => $value->deadline,
                "status" => $value->status,
                "is_pinned" => $value->is_pinned,
                "overdue_task" => $value->overdue_task,
                "none_overdue_task" => $value->none_overdue_task,
                "quality" => $value->quality,
            ]);
        }

        return $newData;
    }

    /** Tasks with tree-data select dropdown
     *
     *
     * @group Department Tasks
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

        $user = Auth()->user();
        //employees role
        $leaderIdsRole = config('const.employee_id_leader_roles');
        $pmIdsRole = config('const.employee_id_pm_roles');

        //Add SQL according to requested search conditions
        //On request
        $requestDatas = $request->all();

        $parentIds = Task::join('task_projects', function ($join) use ($requestDatas) {
            $join->on('task_projects.task_id', '=', 'tasks.id')->whereNull('task_projects.deleted_at');
            if (isset($requestDatas['project_id']) && count($requestDatas['project_id']) > 0) {
                $projectIds = array_filter($requestDatas['project_id'], 'is_int');
                $join->whereIn('task_projects.project_id', $projectIds);
            }
        })
        ->select('tasks.id', 'tasks.root_parent')
        ->where(function ($query) use ($requestDatas, $user, $pmIdsRole) {
            if (in_array($user->id, $pmIdsRole)) {
                if (!empty($requestDatas['department_id'])) {
                    $query->where('tasks.department_id', $requestDatas['department_id']);
                } else {
                    $query->where('tasks.department_id', $user->department_id);
                }
            } else {
                $query->where('tasks.department_id', $user->department_id);
            }
        })
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
        // if ($request->id) {
        //     $sql .= " WHERE parent.id != ".$request->id;
        // }

        $model = new Task();
        $result = DB::connection($model->getConnectionName())->select($sql);
        $data = $this->createHierarchicalTree($result);

        if (!$data) {
            return [];
        }

        return response()->json($data);
    }

    public function getSelectBoxesByDepartmentId(Request $request)
    {
        try {
            $user = Auth()->user();
            $requestDatas = $request->all();
            
            //employees
            $employees = User::where('user_status', '!=', 2)
                            ->where(function ($query) use ($requestDatas, $user) {
                                if (isset($requestDatas['department_id']) && !empty($requestDatas['department_id'])) {
                                    $query->where('department_id', $requestDatas['department_id']);
                                } else {
                                    $query->where('department_id', $user->department_id);
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
            ->where(function ($query) use ($requestDatas, $user) {
                if (isset($requestDatas['department_id']) && !empty($requestDatas['department_id'])) {
                    $query->where('department_id', $requestDatas['department_id']);
                } else {
                    $query->where('department_id', $user->department_id);
                }
            })
            ->get();

            $data = [
                'employees' => $employees,
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

    public function getTaskDescriptionById(Request $request)
    {
        try {
            //code...
            $task = Task::select('id', 'name', 'description')
                ->with(['files' => function ($query) {
                    $query->select('id', 'path', 'task_id');
                }])
                ->where('id', $request->id)->first();

            return response()->json($task);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Task Store
     *
     * @group Department Task
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
            //employees role
            $leaderIdsRole = config('const.employee_id_leader_roles');
            $pmIdsRole = config('const.employee_id_pm_roles');

            $user = Auth()->user();

            //init root_parent and task_parent
            $root_parent = null;
            $task_parent = null;

            $name = isset($requestDatas['name']) ? $requestDatas['name'] : null;
            //init saveData
            $saveData = [
                'name' => $name,
                'code' => $this->generateCodeFromName($name),
                'description' => isset($requestDatas['description']) ? $requestDatas['description'] :  null,
                'task_parent' => $task_parent,
                'root_parent' => $root_parent,
                'department_id' => $user->department_id,
                'user_id' => $user->id,
                'weight' => isset($requestDatas['weight']) ? $requestDatas['weight'] : null,
                'priority' => isset($requestDatas['priority']) ? $requestDatas['priority'] : null,
                'sticker_id' => isset($requestDatas['sticker_id']) ? $requestDatas['sticker_id'] : null,
                'status' => isset($requestDatas['status']) ? $requestDatas['status'] : 1,
            ];

            if (in_array($user->id, $pmIdsRole)) {
                $saveData['department_id'] = isset($requestDatas['department_id'])
                    ? $requestDatas['department_id'] : $user->department_id;
            }

            if (in_array($user->id, $leaderIdsRole)) {
                $saveData['user_id'] = isset($requestDatas['user_id'])
                    ? $requestDatas['user_id'] : $user->id;

                if (isset($requestDatas['task_parent']) && !empty($requestDatas['task_parent'])) {
                    $task_parent = $requestDatas['task_parent'];
                    //find root_parent by task_parent
                    $root_parent = $this->getRootParentId($task_parent);

                    $saveData['task_parent'] = $task_parent;
                    $saveData['root_parent'] = $root_parent;
                }
            }

            Task::performTransaction(function ($model) use ($saveData, $requestDatas) {
                $task = Task::create($saveData);

                //insert task_projects table
                if (isset($requestDatas['project_ids']) && count($requestDatas['project_ids']) > 0) {
                    foreach ($requestDatas['project_ids'] as $value) {
                        TaskProject::create([
                            'task_id' => $task->id,
                            'project_id' => $value,
                        ]);
                    }
                } else {
                    TaskProject::create([
                        'task_id' => $task->id
                    ]);
                }

                //insert task_timings table
                if (isset($requestDatas['start_time']) && !empty($requestDatas['start_time'])) {
                    $period = CarbonPeriod::create(
                        Carbon::create($requestDatas['start_time'])->format('Y-m-d'),
                        Carbon::create($requestDatas['end_time'])->format('Y-m-d')
                    );
        
                    foreach ($period->toArray() as $item) {
                        //insert to task_timings table
                        TaskTiming::create([
                            'task_id' => $task->id,
                            'work_date' => $item->format('Y-m-d'),
                            'type' => 0
                        ]);
                    }
                } else {
                    //insert to task_timings table
                    TaskTiming::create([
                        'task_id' => $task->id,
                        'type' => 0
                    ]);
                }
            });

            return response()->json([
                'success' => __('MSG-S-003'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Update Task
     *
     * @group Department Task
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
        $requestDatas = $request->all();

        try {
            //employees role
            $leaderIdsRole = config('const.employee_id_leader_roles');
            $pmIdsRole = config('const.employee_id_pm_roles');

            $user = Auth()->user();

            $task = Task::findOrFail($requestDatas['id']);

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

            //init update data
            $name = isset($requestDatas['name']) ? $requestDatas['name'] : null;

            $task->name = $name;
            if ($task->isDirty('name')) {
                $task->code = $this->generateCodeFromName($name);
            }

            $task->description = $requestDatas['description'];
            if ($root_parent) {
                $task->root_parent = $root_parent;
            }

            if ($requestDatas['type'] == 'child') {
                if (array_key_exists('weight', $requestDatas)) {
                    $task->weight = $requestDatas['weight'];
                    if ($task->isDirty('weight')) {
                        CommonController::updateWeightTaskProjects($task);
                    }
                }
                if (array_key_exists('priority', $requestDatas)) {
                    $task->priority = $requestDatas['priority'];
                }
                if (array_key_exists('sticker_id', $requestDatas)) {
                    $task->sticker_id = $requestDatas['sticker_id'];
                }

                if (in_array($user->id, $pmIdsRole)) {
                    if (array_key_exists('department_id', $requestDatas)) {
                        $task->department_id = $requestDatas['department_id'];
                    }
                }

                $task->status = $requestDatas['status'];
                if ($requestDatas['status'] == 4) {
                    //if status is 4 (completed), progress will be 100
                    $task->progress = 100;
                }
                $task->user_id = $requestDatas['user_id'];
            }

            Task::performTransaction(function ($model) use ($task, $requestDatas) {
                //insert task
                if ($task->save()) {
                    CommonController::syncTaskProjects($task, $requestDatas['project_ids']);

                    TaskDeadline::where('task_id', $task->id)->update(['status' => $task->status]);
                }
            });
        } catch (ExclusiveLockException $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
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

            if (array_key_exists('description', $requestDatas)) {
                $task->description = $requestDatas['description'];
            }

            if (array_key_exists('sticker_id', $requestDatas)) {
                $task->sticker_id = $requestDatas['sticker_id'];
            }

            if (array_key_exists('priority', $requestDatas)) {
                $task->priority = $requestDatas['priority'];
            }

            if (array_key_exists('project_ids', $requestDatas)) {
                CommonController::syncTaskProjects($task, $requestDatas['project_ids']);
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
                }

                TaskDeadline::where('task_id', $task->id)->update(['status' => $task->status]);
            }

            if (array_key_exists('is_pinned', $requestDatas)) {
                $pinned = $requestDatas['is_pinned'];
                if (empty($pinned)) {
                    PinTask::where('task_id', $task->id)->where('user_id', Auth()->user()->id)->delete();
                } else {
                    PinTask::create([
                        'task_id' => $task->id,
                        'user_id' => Auth()->user()->id
                    ]);
                }
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

    /** Delete task
     *
     * @group Department Task
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

            //check if the task id has its child
            $child = Task::where('task_parent', $task->id)->first();
            if ($child) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-021', ['attribute' => $child->name])
                ], Response::HTTP_NOT_FOUND);
            }

            //check task is exists in task_timings or not
            $taskTiming = TaskTiming::where('task_id', $task->id)
                ->whereIn('type', [1,2,3,4])
                ->first();

            if ($task->task_parent || $taskTiming) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-020', ['attribute' => $task->name])
                ], Response::HTTP_NOT_FOUND);
            }

            Task::performTransaction(function ($model) use ($task, $id) {
                //delete task
                if ($task->delete()) {
                    //delete task timing projects
                    TaskTimingProject::where('task_id', $id)->delete();
                }
            });
        } catch (ExclusiveLockException $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'errors' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Task
     *
     * Get task by id
     *
     * @group Department Task
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
                "weight" => $task->weight,
                "priority" => $task->priority,
                "sticker_id" => $task->sticker_id,
                "department_id" => $task->department_id,
                "status" => $task->status,
                "task_parent" => $task->task_parent,
                "user_id" => $task->user_id,
                "description" => $task->description,
                "project_id" => $task->project_id,
                "updated_at" => $task->updated_at,
                "start_time" => $task->start_time ? Carbon::create($task->start_time)->format('d/m/Y') : null,
                "end_time" => $task->end_time ? Carbon::create($task->end_time)->format("d/m/Y") : null,
            ];

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

        $model = new Task();
        $result = DB::connection($model->getConnectionName())->select($sql);

        return $result[0]->id;
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
        $user = Auth()->user();
        //employees role
        $leaderIdsRole = config('const.employee_id_leader_roles');
        $pmIdsRole = config('const.employee_id_pm_roles');

        $addedQuery = $query;

        //Change the SQL according to the requested search conditions
        if (!empty($requestDatas['name'])) {
            $addedQuery = $addedQuery->where(
                DB::raw('lower(tasks.name)'),
                'LIKE',
                '%'.mb_strtolower(urldecode($requestDatas['name']), 'UTF-8').'%'
            );
        }

        if (in_array($user->id, $pmIdsRole)) {
            if (!empty($requestDatas['department_id'])) {
                $addedQuery = $addedQuery->where('tasks.department_id', $requestDatas['department_id']);
            } else {
                $addedQuery = $addedQuery->where('tasks.department_id', $user->department_id);
            }
        } else {
            $addedQuery = $addedQuery->where('tasks.department_id', $user->department_id);
        }

        if (isset($requestDatas['user_id'])) {
            if (is_array($requestDatas['user_id'])) {
                if (isset($requestDatas['mode']) && $requestDatas['mode'] === "bug") {
                    $addedQuery = $addedQuery->whereIn('task_assignments.assigned_user_id', $requestDatas['user_id']);
                } else {
                    $addedQuery = $addedQuery->whereIn('tasks.user_id', $requestDatas['user_id']);
                }

            } else {
                if (isset($requestDatas['mode']) && $requestDatas['mode'] === "bug") {
                    $addedQuery = $addedQuery->where('task_assignments.assigned_user_id', $requestDatas['user_id']);
                } else {
                    $addedQuery = $addedQuery->where('tasks.user_id', $requestDatas['user_id']);
                }
            }
        }

        if (isset($requestDatas['status'])) {
            if (is_array($requestDatas['status'])) {
                $addedQuery = $addedQuery->whereIn('tasks.status', $requestDatas['status']);
            } else {
                $addedQuery = $addedQuery->where('tasks.status', $requestDatas['status']);
            }
        }

        if (isset($requestDatas['weighted'])) {
            $addedQuery = $addedQuery->where(function ($query) {
                $query->whereNull('tasks.weight')->orWhere('tasks.weight', '=', 0.0);
            });
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
            $result = DB::connection($model->getConnectionName())->select($sql);

            if (count($result) > 0) {
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
}
