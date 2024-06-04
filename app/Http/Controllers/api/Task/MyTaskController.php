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
use App\Models\TaskFile;
use App\Models\TaskProject;
use App\Models\TaskTiming;
use App\Models\TaskTimingProject;
use App\Models\FavoriteTask;
use App\Models\PinTask;
use App\Models\Sticker;
use App\Models\Priority;
use App\Http\Requests\api\Task\Me\GetTaskListRequest;
use App\Http\Requests\api\Task\Me\TaskRegisterRequest;
use App\Http\Requests\api\Task\Me\GetTaskByIdRequest;
use App\Http\Requests\api\Task\Me\TaskEditRequest;
use App\Http\Requests\api\Task\Me\TaskQuickEditRequest;
use App\Http\Requests\api\Task\Me\TaskDeleteRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * My Task API
 *
 * @group My Task
 */
class MyTaskController extends Controller
{
    /** Select boxes data
     *
     *
     * @group My Task
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
            $status = config('const.status');
            $user = Auth()->user();

            $taskTimingTypes = config('const.task_timings_type');

            $projects = Project::select('id', 'name')->orderBy('ordinal_number', 'asc')->get();

            $stickers = Sticker::select('id', 'name')
                        ->where('department_id', $user->department_id)
                        ->get();

            $userIdLogin = $user->id;
            $suggestionList = FavoriteTask::join('tasks', function ($join) {
                $join->on('tasks.id', '=', 'favorite_tasks.task_id')->whereNull('tasks.deleted_at');
            })
            ->select('tasks.name as value', 'tasks.id as id_suggest')
            ->where('favorite_tasks.user_id', $userIdLogin)->get();

            $data = [
                'projects' => $projects,
                'status' => $status,
                'task_timing_type' => $taskTimingTypes,
                'stickers' => $stickers,
                'user_session' => [
                    'id' => $userIdLogin,
                    'department_id' => $user->department_id,
                    'position' => $user->position
                ],
                'suggestion_list' => $suggestionList
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
     * @group My Task
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
            //stickers
            $stickers = Sticker::select('id', 'name')
                        ->where('department_id', Auth()->user()->department_id)
                        ->get()->toArray();

            //status
            $status = config('const.status');

            $projects = Project::select('id', 'name')->orderBy('ordinal_number', 'asc')->get();

            $data = [
                'projects' => $projects,
                'status' => $status,
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

    /** Tasks list for me
     *
     *
     * @group My Tasks
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
            $userIdLogin = Auth()->user()->id;
            if (in_array($userIdLogin, [107,45,49,51]) && !empty($requestDatas['user_id'])) {
                $userIdLogin = $requestDatas['user_id'];
            }
            //employees search fav tasks with option is 2(today)
            $this->createTodayTask($requestDatas, $userIdLogin);

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

            $query = Task::join('task_timings', function ($join) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');
            })
            ->leftJoin('task_assignments', function ($join) {
                $join->on('task_assignments.id', '=', 'task_timings.task_assignment_id')
                    ->whereNull('task_assignments.deleted_at');
            })
            ->when(!empty($requestDatas['option']) && $requestDatas['option'] == 6, function ($query) {
                $query->join('favorite_tasks', function ($join) {
                    $join->on('tasks.id', '=', 'favorite_tasks.task_id')->whereNull('favorite_tasks.deleted_at');
                });
            }, function ($query) {
                $query->leftJoin('favorite_tasks', function ($join) {
                    $join->on('tasks.id', '=', 'favorite_tasks.task_id')->whereNull('favorite_tasks.deleted_at');
                });
            })
            ->when(!empty($requestDatas['option']) && $requestDatas['option'] == 7, function ($query) {
                $query->join('pin_tasks', function ($join) {
                    $join->on('tasks.id', '=', 'pin_tasks.task_id')->whereNull('pin_tasks.deleted_at')
                    ->where('pin_tasks.user_id', Auth()->user()->id);
                });
            }, function ($query) {
                $query->leftJoin('pin_tasks', function ($join) {
                    $join->on('tasks.id', '=', 'pin_tasks.task_id')->whereNull('pin_tasks.deleted_at')
                    ->where('pin_tasks.user_id', Auth()->user()->id);
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
            ->selectRaw(DB::raw("
                tasks.id as id,
                tasks.name as name,
                tasks.sticker_id as sticker_id,
                coalesce(nullif(pj.project_ids, '{null}'), null) as project_id,
                tasks.department_id as department_id,
                min(task_timings.work_date) as start_work_date,
                max(task_timings.work_date) as end_work_date,
                coalesce(sum(task_timings.estimate_time), 0) as total_estimate_time,
                coalesce(sum(task_timings.time_spent), 0) as total_time_spent,
                tasks.description as description,
                tasks.progress as progress,
                tasks.user_id as user_id,
                tasks.deadline as deadline,
                tasks.status as status,
                case when favorite_tasks.task_id is not null then 1 else 0 end as favorite,
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
            ->where(function ($query) use ($userIdLogin) {
                $query->where('tasks.user_id', $userIdLogin)
                    ->orWhere('task_assignments.assigned_user_id', $userIdLogin);
            })
            ->where(function ($query) use ($userIdLogin) {
                $query->where('task_timings.type', 0)
                    ->orWhere('task_assignments.assigned_user_id', $userIdLogin);
            })
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
            
            $query = $query->groupBy('tasks.id', 'pj.project_ids', 'favorite_tasks.task_id', 'pin_tasks.task_id');

            $total = $query->get()->count();

            // Assuming $requestDatas contains the column and order parameters.
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

            $tasks = $query->offset(($requestDatas['current_page'] - 1) * $requestDatas['per_page'])
                            ->limit($requestDatas['per_page'])
                            ->get();

            //no search results
            if (count($tasks) === 0) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
            }

            $data = [
                'items' => $this->transferTasks($tasks),
                'currentPage' => $requestDatas['current_page'],
                // 'perPage' => $requestDatas['per_page'],
                'totalItems' => $total
            ];

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    private function createTodayTask($requestDatas, $userIdLogin)
    {
        if (isset($requestDatas['option']) && $requestDatas['option'] == 2) {
            if (isset($requestDatas['id_suggest'])) {
                $taskTiming = TaskTiming::join('tasks', function ($join) {
                    $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('tasks.deleted_at');
                })
                ->where('tasks.user_id', $userIdLogin)
                ->whereDate('task_timings.work_date', Carbon::now()->format('Y/m/d'))
                ->where('task_timings.task_id', $requestDatas['id_suggest'])
                ->first();

                if (!$taskTiming) {
                    TaskTiming::create([
                        'task_id' => $requestDatas['id_suggest'],
                        'type' => 0,
                        'work_date' => Carbon::now()->format('Y/m/d')
                    ]);
                }
            }
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
                    then task_timings.time_spent else 0 end), 0) as total_time_spent"))
            ->where('tasks.user_id', Auth()->user()->id);

            $weightQuery = Task::query()
                        ->selectRaw(DB::raw("
                            coalesce(sum(tasks.weight) filter (where tasks.status = 4), 0) as total_weight_complete,
                            coalesce(sum(tasks.weight), 0) as total_weight"))
                        ->where('tasks.user_id', Auth()->user()->id);
            
            //Add SQL according to requested search conditions
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
                "description" => $value->description,
                "sticker_id" => $value->sticker_id,
                "project_id" => $value->project_id,
                "department_id" => $value->department_id,
                "user_id" => $value->user_id,
                "start_time" => $startWorkDate,
                "end_time" => $endWorkDate,
                "total_estimate_time" => $value->total_estimate_time,
                "total_time_spent" => $value->total_time_spent,
                "progress" => $value->progress,
                "deadline" => $value->deadline,
                "status" => $value->status,
                "favorite" => $value->favorite,
                "is_pinned" => $value->is_pinned,
                "overdue_task" => $value->overdue_task,
                "none_overdue_task" => $value->none_overdue_task
            ]);
        }

        return $newData;
    }

    /** Task Store
     *
     * @group My Task
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
            $name = isset($requestDatas['name']) ? $requestDatas['name'] : null;
            $root_parent = null;
            $task_parent = null;
            //init saveData
            $saveData = [
                'name' => $name,
                'code' => $this->generateCodeFromName($name),
                'task_parent' => $task_parent,
                'root_parent' => $root_parent,
                'description' => isset($requestDatas['description']) ? $requestDatas['description'] : null,
                'user_id' => Auth()->user()->id,
                'department_id' => Auth()->user()->department_id,
                'sticker_id' => isset($requestDatas['sticker_id']) ? $requestDatas['sticker_id'] : null,
                'status' => isset($requestDatas['status']) ? $requestDatas['status'] : 1,
            ];
            if (isset($requestDatas['task_parent']) && !empty($requestDatas['task_parent'])) {
                $task_parent = $requestDatas['task_parent'];
                //find root_parent by task_parent
                $root_parent = $this->getRootParentId($task_parent);

                $saveData['task_parent'] = $task_parent;
                $saveData['root_parent'] = $root_parent;
            }

            Task::performTransaction(function ($model) use ($saveData, $requestDatas) {
                $task = Task::create($saveData);

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
            });

            return response()->json([
                'success' => __('MSG-S-003'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    /** Update Task
     *
     * @group My Task
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
            $task = Task::findOrFail($requestDatas['id']);
            $root_parent = null;
            $task_parent = null;
            //exclusion control
            // $task->setCheckUpdatedAt($requestDatas['check_updated_at']);

            //init update data
            $name = isset($requestDatas['name']) ? $requestDatas['name'] : null;

            $task->name = $name;
            if ($task->isDirty('name')) {
                $task->code = $this->generateCodeFromName($name);
            }
            $task->description = isset($requestDatas['description']) ? $requestDatas['description'] : null;
            $task->sticker_id = isset($requestDatas['sticker_id']) ? $requestDatas['sticker_id'] : null;
            $task->status = isset($requestDatas['status']) ? $requestDatas['status'] : null;
            if ($task->status == 4) {
                //if status is 4 (completed), progress will be 100
                $task->progress = 100;
            }
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
            $task = Task::findOrFail($requestDatas['id']);

            Task::performTransaction(function ($model) use ($requestDatas, $task) {
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

                if (array_key_exists('project_ids', $requestDatas)) {
                    CommonController::syncTaskProjects($task, $requestDatas['project_ids']);
                }

                if (array_key_exists('progress', $requestDatas)) {
                    $task->progress = $requestDatas['progress'] ? $requestDatas['progress'] : 0;
                }

                if (array_key_exists('status', $requestDatas)) {
                    $task->status = $requestDatas['status'];
                    if ($requestDatas['status'] == 4) {
                        //if status is 4 (completed), progress will be 100
                        $task->progress = 100;
                    }

                    TaskDeadline::where('task_id', $task->id)->update(['status' => $requestDatas['status']]);
                }

                if (array_key_exists('favorite', $requestDatas)) {
                    $favorite = $requestDatas['favorite'];
                    if (empty($favorite)) {
                        FavoriteTask::where('task_id', $task->id)->where('user_id', Auth()->user()->id)->delete();
                    } else {
                        FavoriteTask::create([
                            'task_id' => $task->id,
                            'user_id' => Auth()->user()->id
                        ]);
                    }
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

                //insert task
                $task->save();
            });

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    /** Delete task
     *
     * @group My Task
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
                if ($task->delete()) {
                    // Delete task timing projects
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

            //check if the id is exist tasks table with task_parent is not null
            // $task = Task::whereIn('id', $requestDatas['ids'])
            //     ->whereNotNull('task_parent')
            //     ->first();
            // if ($task) {
            //     return response()->json([
            //         'status' => Response::HTTP_NOT_FOUND,
            //         'errors' => __('MSG-E-020', ['attribute' => $task->name])
            //     ], Response::HTTP_NOT_FOUND);
            // }

            //check if the task id has its child
            $child = Task::whereIn('task_parent', $requestDatas['ids'])->first();
            if ($child) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-021', ['attribute' => $child->name])
                ], Response::HTTP_NOT_FOUND);
            }

            //check if the task_id is exist task_timings table with type is bug or feedback
            $taskTiming = TaskTiming::join('tasks', function ($join) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('tasks.deleted_at');
            })
            ->select('tasks.name')
            ->whereIn('task_id', $requestDatas['ids'])->whereIn('type', [1,2,3,4])->first();
            if ($taskTiming) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-020', ['attribute' => $taskTiming->name])
                ], Response::HTTP_NOT_FOUND);
            }

            Task::performTransaction(function ($model) use ($requestDatas) {
                // Delete tasks with the given IDs
                Task::whereIn('id', $requestDatas['ids'])->delete();
                // Delete task files with the given IDs
                TaskFile::whereIn('task_files.task_id', $requestDatas['ids'])->delete();
                // Delete task_timings with the given IDs
                TaskTiming::whereIn("task_timings.task_id", $requestDatas['ids'])->delete();
                // Delete task_projects with the given IDs
                TaskProject::whereIn("task_projects.task_id", $requestDatas['ids'])->delete();
                // Delete task timing projects
                TaskTimingProject::whereIn('task_id', $requestDatas['ids'])->delete();
                // Delete favorite task
                FavoriteTask::whereIn('task_id', $requestDatas['ids'])->delete();
                // Delete pin task
                PinTask::whereIn('task_id', $requestDatas['ids'])->delete();
            });
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
     * @group My Task
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
                min(task_timings.work_date) as start_time,
                max(task_timings.work_date) as end_time,
                tasks.description as description,
                tasks.status as status,
                tasks.task_parent as task_parent,
                coalesce(nullif(pj.project_ids, '{null}'), null) as project_id,
                tasks.updated_at as updated_at"))
            ->where('tasks.id', $request->id)->groupBy('tasks.id', 'pj.project_ids')->first();

            $data = [
                "id" => $task->id,
                "name" => $task->name,
                "description" => $task->description,
                "project_id" => $task->project_id,
                "updated_at" => $task->updated_at,
                "start_time" => Carbon::create($task->start_time)->format("d/m/Y"),
                "end_time" => Carbon::create($task->end_time)->format("d/m/Y"),
                "sticker_id" => $task->sticker_id,
                "task_parent" => $task->task_parent,
                "status" => $task->status
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
            $addedQuery = $addedQuery->where(
                DB::raw('lower(tasks.name)'),
                'LIKE',
                '%'.mb_strtolower(urldecode($requestDatas['name']), 'UTF-8').'%'
            );
        }

        if (isset($requestDatas['status'])) {
            if (is_array($requestDatas['status'])) {
                $addedQuery = $addedQuery->whereIn('tasks.status', $requestDatas['status']);
            } else {
                $addedQuery = $addedQuery->where('tasks.status', $requestDatas['status']);
            }
        }

        return $addedQuery;
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
}
