<?php

namespace App\Http\Controllers\api\Task;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\TaskTiming;
use App\Models\TaskTimingProject;
use App\Models\Task;
use App\Models\TaskProject;
use App\Models\Sticker;
use App\Models\Priority;
use Carbon\Carbon;
use App\Http\Requests\api\Task\TaskTiming\TaskTimingGetListRequest;
use App\Http\Requests\api\Task\TaskTiming\TaskTimingRegisterRequest;
use App\Http\Requests\api\Task\TaskTiming\TaskTimingEditRequest;
use App\Http\Requests\api\Task\TaskTiming\TaskTimingDeleteRequest;
use App\Http\Controllers\api\CommonController;

/**
 * Task Timing API
 *
 * @group Task Timing
 */
class TaskTimingController extends Controller
{
    public function getSelboxes(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $type = config('const.task_timings_type');

            //group employees by department
            $employees = User::select('id', 'fullname')
            ->where(function ($query) use ($requestDatas) {
                if (!empty($requestDatas['department_id'])) {
                    $query->where('department_id', $requestDatas['department_id']);
                }
            })
            ->where('user_status', 1)
            ->where('position', '!=', 3)
            ->get();

            //group stickers by department
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
                'level_10'
            )
            ->where(function ($query) use ($requestDatas) {
                if (!empty($requestDatas['department_id'])) {
                    $query->where('department_id', $requestDatas['department_id']);
                }
            })
            ->get();

            //group priorities by department
            $priorities = Priority::select('id', 'label')->get();

            //projects selbox
            $projects = [];
            if (isset($requestDatas['task_id']) && !empty($requestDatas['task_id'])) {
                $projects = TaskProject::join('projects', function ($join) {
                    $join->on('projects.id', '=', 'task_projects.project_id')
                        ->whereNull('projects.deleted_at');
                })
                ->select('projects.id', 'projects.name')
                ->where('task_id', $requestDatas['task_id'])
                ->get();
            }

            $data = [
                'type' => $type,
                'employees' => $employees,
                'projects' => $projects,
                'stickers' => $stickers,
                'priorities' => $priorities,
                'employee_id_login' => Auth()->user()->id,
                'position' => Auth()->user()->position
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

    /** Task Timing List
     *
     *
     * @group Task Timing
     *
     * @bodyParam task_id biginteger required Mã công việc
     *
     * @response 200 {
     *  [
     *      {
     *          "id": 1,
     *          "work_date": "12/12/2023",
     *          "estimate_time": 0.5,
     *          "time_spent": 1,
     *          "updated_at": "2023-02-02 12:12:12"
     *      },
     *      ...
     *  ]
     * }
     * @response 404 {
     *    "errors": "Không có dữ liệu được tìm thấy"
     * }
     * @response 422 {
     *      "status": 422,
     *      "errors": "Công việc không tồn tại",
     *      "errors_list": {
     *          "task_id": [
     *              "Công việc không tồn tại"
     *          ]
     *      }
     * }
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function getList(TaskTimingGetListRequest $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $subquery = TaskTimingProject::select('task_timing_id', DB::raw('array_agg(project_id) as project_ids'))
            ->whereNull('deleted_at')
            ->groupBy('task_timing_id');

            $query = TaskTiming::join('tasks', function ($join) {
                $join->on('tasks.id', '=', 'task_timings.task_id')
                    ->whereNull('tasks.deleted_at');
            })
            ->leftJoinSub($subquery, 'pj', function ($join) {
                $join->on('pj.task_timing_id', '=', 'task_timings.id');
            })
            ->select(
                'task_timings.id as id',
                'tasks.name as name',
                DB::raw("to_char(task_timings.work_date, 'DD-MM-YYYY') as work_date"),
                DB::raw("coalesce(nullif(pj.project_ids, '{null}'), null) as project_id"),
                'task_timings.estimate_time as estimate_time',
                'task_timings.time_spent as time_spent',
                'task_timings.description as description',
                'task_timings.updated_at as updated_at'
            )
            ->whereNull('task_timings.deleted_at')
            ->where($requestDatas['column'], $requestDatas['id'])
            ->where('task_timings.type', 0)
            ->orderByRaw("task_timings.work_date is null desc, task_timings.work_date desc");
            // ->get();
            $total = $query->get()->count();
            if ($total/$requestDatas['per_page'] < $requestDatas['current_page']) {
                $requestDatas['current_page'] = ceil($total/$requestDatas['per_page']);
            }

            $taskTimings = $query->offset(($requestDatas['current_page'] - 1) * $requestDatas['per_page'])
                            ->limit($requestDatas['per_page'])
                            ->get();

            $data = [
                'data' => $taskTimings,
                'currentPage' => $requestDatas['current_page'],
                'totalItems' => $total
            ];

            //no search results
            if (count($taskTimings) === 0) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getIssues(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $taskTimings = TaskTiming::join('tasks', function ($join) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('tasks.deleted_at');
            })
            ->join('task_assignments', function ($join) {
                $join->on('task_assignments.id', '=', 'task_timings.task_assignment_id')
                    ->whereNull('task_assignments.deleted_at');
            })
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'tasks.user_id')
                    ->whereNull('tasks.deleted_at');
            })
            ->join('departments', function ($join) {
                $join->on('departments.id', '=', 'tasks.department_id')
                    ->whereNull('departments.deleted_at');
            })
            ->select(
                'task_timings.id as id',
                'tasks.name as name',
                'tasks.user_id as task_user_id',
                'task_timings.work_date as work_date',
                'task_assignments.assigned_user_id as assigned_user_id',
                'task_timings.sticker_id as sticker_id',
                'task_timings.task_assignment_id as task_assignment_id',
                'task_timings.priority as priority',
                'task_timings.weight as weight',
                'task_timings.estimate_time as estimate_time',
                'task_timings.time_spent as time_spent',
                'task_timings.description as description',
                'task_timings.type as type',
                'task_timings.updated_at as updated_at',
                'users.fullname as fullname',
                // 'tasks.department_id as department_id',
                'departments.name as department_id',
            )
            ->where([
                ['task_timings.type', '!=', 0],
                ['task_timings.'.$requestDatas['column'], $requestDatas['id']]
            ])
            ->get();
            // $taskTimings = CommonController::getDepartmentNameAfterQuery($taskTimings);

            //no search results
            if (count($taskTimings) === 0) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json($taskTimings);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /** Task Timing Store
     *
     * @group Task Timing
     *
     * @bodyParam task_id biginteger required Mã công việc
     *
     * @response 404 {
     *    'status' : 404,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 422 {
     *      "status": 422,
     *      "errors": "Công việc không tồn tại",
     *      "errors_list": {
     *          "task_id": [
     *              "Công việc không tồn tại"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function store(TaskTimingRegisterRequest $request)
    {
        $requestDatas = $request->all();

        try {
            TaskTiming::performTransaction(function ($model) use ($requestDatas) {
                TaskTiming::create([
                    'task_id' => $requestDatas['task_id'],
                    'type' => 0,
                ]);
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

    public function update(TaskTimingEditRequest $request)
    {
        $requestDatas = $request->all();

        try {
            $model = new TaskTiming();
            $connectionName = $model->getConnectionName();
            //start transaction control
            DB::connection($connectionName)->beginTransaction();

            $record = TaskTiming::findOrFail($requestDatas['id']);

            if (array_key_exists('work_date', $requestDatas)) {
                $record->work_date = isset($requestDatas['work_date']) ? $requestDatas['work_date'] : null;
            }

            if (array_key_exists('project_ids', $requestDatas)) {
                $currentProjectIds = $record->taskTimingProjects()->pluck('project_id')->toArray();

                $projectIdsToAdd = array_diff($requestDatas['project_ids'], $currentProjectIds);
                $projectIdsToRemove = array_diff($currentProjectIds, $requestDatas['project_ids']);

                // Add the new project_id associations
                if (count($projectIdsToAdd) > 0) {
                    foreach ($projectIdsToAdd as $projectId) {
                        $record->taskTimingProjects()->create([
                            'task_timing_id' => $record->id,
                            'task_id' => $record->task_id,
                            'project_id' => $projectId
                        ]);
                    }
                }

                // Remove the old project_id associations
                if (count($projectIdsToRemove) > 0) {
                    $record->taskTimingProjects()->whereIn('project_id', $projectIdsToRemove)->delete();
                }
            }

            if (array_key_exists('sticker_id', $requestDatas)) {
                $record->sticker_id = isset($requestDatas['sticker_id']) ? $requestDatas['sticker_id'] : null;
            }

            if (array_key_exists('priority', $requestDatas)) {
                $record->priority = isset($requestDatas['priority']) ? $requestDatas['priority'] : null;
            }

            if (array_key_exists('weight', $requestDatas)) {
                $record->weight = isset($requestDatas['weight']) ? $requestDatas['weight'] : null;
            }

            if (array_key_exists('estimate_time', $requestDatas)) {
                $record->estimate_time = isset($requestDatas['estimate_time']) ? $requestDatas['estimate_time'] : null;
            }

            if (array_key_exists('time_spent', $requestDatas)) {
                $record->time_spent = isset($requestDatas['time_spent']) ? $requestDatas['time_spent'] : null;
            }

            if (array_key_exists('description', $requestDatas)) {
                $record->description = isset($requestDatas['description']) ? $requestDatas['description'] : null;
            }

            if (array_key_exists('type', $requestDatas)) {
                $record->type = isset($requestDatas['type']) ? $requestDatas['type'] : 0;
            }

            //insert task
            $record->save();

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

    /** Delete Task Timing
     *
     * @group Task Timing
     *
     * @bodyParam id bigint required ID Task Timing
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
    public function destroy(TaskTimingDeleteRequest $request)
    {
        $requestDatas = $request->all();

        try {
            $taskTiming = TaskTiming::findOrFail($requestDatas['id']);
            //exclusion control
            if (isset($requestDatas['check_updated_at'])) {
                $taskTiming->setCheckUpdatedAt($requestDatas['check_updated_at']);
            }

            TaskTiming::performTransaction(function ($model) use ($taskTiming) {
                //delete project
                $taskTiming->delete();
            });

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
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

    /** Append SQL if json is requested
     *
     * @param  $requestDatas
     * @param  $query
     * @return $addedQuery
    */
    private function addSqlWithSorting($requestDatas, $query)
    {
        $addedQuery = $query;

        $addedQuery = $addedQuery->where('task_timings.'.$requestDatas['column'], $requestDatas['id']);

        return $addedQuery;
    }

    public function deleteMultiple(Request $request)
    {
        $requestDatas = $request->all();

        try {
            $taskTiming = TaskTiming::whereIn('id', $requestDatas['id'])->get();
            if ($taskTiming->count() !== count($requestDatas['id'])) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }
            TaskTiming::whereIn("task_timings.id", $requestDatas['id'])->delete();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
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
}
