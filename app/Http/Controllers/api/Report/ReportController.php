<?php

namespace App\Http\Controllers\api\Report;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api\CommonController;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Task;
use App\Models\TaskTiming;
use App\Models\WeightedFluctuation;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Models\Project;
use App\Http\Requests\api\Report\GetReportRequest;
use App\Http\Requests\api\Report\GetUserReportRequest;
use Carbon\Carbon;
use File;
use Storage;

/**
 * Report API
 *
 * @group Report
 */
class ReportController extends Controller
{
    /** Report
     *
     *
     * @group Report
     *
     * @bodyParam start_time date optional Ngày bắt đầu
     * @bodyParam end_time date optional Ngày kết thúc
     * @bodyParam project_ids[] array optional Dự án
     * @bodyParam department_ids[] array optional Bộ phận
     *
     * @response 200 {
     *  [
     *      {
     *          "label": "Tổng",
     *          "total": "1 công việc",
     *          "dev": "1 công việc",
     *          "gd": "0 công việc",
     *          "art": "0 công việc",
     *          "test": "0 công việc",
     *      },
     *      ...
     *  ]
     * }
     * @response 422 {
     *      "status": 422,
     *      "errors": "Ngày kết thúc không được nhỏ hơn Ngày bắt đầu",
     *      "errors_list": {
     *          "user_status": [
     *              "Ngày kết thúc không được nhỏ hơn Ngày bắt đầu"
     *          ]
     *      }
     * }
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function getReport(GetReportRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $departments = config('const.departments');

            $isFilterProjectIds = false;
            if (isset($requestDatas['project_ids']) && count($requestDatas['project_ids']) > 0) {
                $isFilterProjectIds = true;
            }

            $tasks = Task::selectRaw("
                tasks.id as id,
                tasks.department_id as department_id,
                tasks.weight as task_weight,
                COALESCE(project_weights.project_weight, 0) as project_weight,
                tasks.status as status
            ")
            ->join('task_timings', 'tasks.id', '=', 'task_timings.task_id')
            ->leftJoin('task_projects', 'tasks.id', '=', 'task_projects.task_id')
            ->leftJoin(DB::raw("
                (SELECT task_id, COALESCE(SUM(weight), 0) as project_weight
                FROM task_projects
                WHERE deleted_at IS NULL
                " . ($isFilterProjectIds === true ? "AND project_id IN (" . implode(',', $requestDatas['project_ids']) . ")" : "") . "
                GROUP BY task_id) as project_weights"), 'tasks.id', '=', 'project_weights.task_id')
            ->whereNull('task_timings.deleted_at')
            ->whereNull('task_projects.deleted_at')
            ->whereNotIn('tasks.department_id', [1, 6, 7, 8, 9, 10, 11])
            ->whereNotNull('tasks.department_id')
            ->when(!empty($requestDatas['start_time']), function ($query) use ($requestDatas) {
                $query->whereDate('task_timings.work_date', '>=', $requestDatas['start_time']);
            })
            ->when(!empty($requestDatas['end_time']), function ($query) use ($requestDatas) {
                $query->whereDate('task_timings.work_date', '<=', $requestDatas['end_time']);
            })
            ->when($isFilterProjectIds === true, function ($query) use ($requestDatas) {
                $query->whereIn('task_projects.project_id', $requestDatas['project_ids']);
            })
            ->when(!empty($requestDatas['department_ids']), function ($query) use ($requestDatas) {
                $query->whereIn('tasks.department_id', $requestDatas['department_ids']);
            })
            ->groupBy('tasks.id', 'project_weights.project_weight')
            ->get();

            $groups = collect($tasks)->groupBy('department_id');

            $summary = $groups->map(function ($tasks, $departmentId) use ($departments) {
                $totalWeight = $tasks->sum(function ($task) {
                    return $task[$task['project_weight'] > 0 ? 'project_weight' : 'task_weight'];
                });
                $totalWeightCompleted = $tasks->where('status', 4)->sum(function ($task) {
                    return $task[$task['project_weight'] > 0 ? 'project_weight' : 'task_weight'];
                });
            
                $totalTasks = $tasks->count();
                $totalCompleted = $tasks->where('status', 4)->count();
                $percentCompleted = $totalTasks > 0 ? round($totalCompleted / $totalTasks * 100, 2) : 0;
            
                $percentWeightCompleted = $totalWeight > 0 ? round($totalWeightCompleted / $totalWeight * 100, 2) : 0;
            
                return [
                    'id' => $departmentId,
                    'department' => $departments[$departmentId],
                    'total' => $totalTasks,
                    'total_slow' => $tasks->where('status', 0)->count(),
                    'total_wait' => $tasks->where('status', 1)->count(),
                    'total_processing' => $tasks->where('status', 2)->count(),
                    'total_pause' => $tasks->where('status', 3)->count(),
                    'total_completed' => $totalCompleted,
                    'total_wait_fb' => $tasks->where('status', 5)->count(),
                    'total_again' => $tasks->where('status', 6)->count(),
                    'total_weight' => round($totalWeight ?? 0, 2),
                    'total_weight_completed' => round($totalWeightCompleted ?? 0, 2),
                    'rate_task_completed' => $percentCompleted,
                    'rate_weight_completed' => $percentWeightCompleted
                ];
            })->values()->all();

            return response()->json($summary);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** User Report
     *
     *
     * @group Report
     *
     * @bodyParam start_time date optional Ngày bắt đầu
     * @bodyParam end_time date optional Ngày kết thúc
     * @bodyParam fullname string optional Họ và tên
     *
     * @response 200 {
     *  [
     *      {
     *          "fullname": "iamadmin",
     *          "total": "1",
     *          "total_slow": "0",
     *          "total_wait": "0",
     *          "total_processing": "0",
     *          "total_pause": "0",
     *          "total_complete: "1",
     *          "total_wait_fb": "0",
     *          "total_again": "0",
     *          "total_complete_slow": "0",
     *          "total_weight": "0"
     *      },
     *      ...
     *  ]
     * }
     *
     * @response 404 {
     *    "errors": "Không có dữ liệu được tìm thấy"
     * }
     *
     * @response 422 {
     *      "status": 422,
     *      "errors": "Ngày kết thúc không được nhỏ hơn Ngày bắt đầu",
     *      "errors_list": {
     *          "user_status": [
     *              "Ngày kết thúc không được nhỏ hơn Ngày bắt đầu"
     *          ]
     *      }
     * }
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function getUserReport(GetUserReportRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $departments = config('const.departments');

            $isFilterProjectIds = false;
            if (isset($requestDatas['project_ids']) && count($requestDatas['project_ids']) > 0) {
                $isFilterProjectIds = true;
            }
            
            $tasks = Task::selectRaw("
                tasks.id as id,
                tasks.department_id as department_id,
                tasks.weight as task_weight,
                tasks.status as status,
                tasks.user_id as user_id,
                tasks.quality as quality,
                COALESCE(project_weights.project_weight, 0) as project_weight
            ")
            ->join('task_timings', 'tasks.id', '=', 'task_timings.task_id')
            ->leftJoin('task_projects', 'tasks.id', '=', 'task_projects.task_id')
            ->leftJoin(DB::raw("
                (SELECT task_id, COALESCE(SUM(weight), 0) as project_weight
                FROM task_projects
                WHERE deleted_at IS NULL
                " . ($isFilterProjectIds === true ? "AND project_id IN (" . implode(',', $requestDatas['project_ids']) . ")" : "") . "
                GROUP BY task_id) as project_weights"), 'tasks.id', '=', 'project_weights.task_id')
            ->whereNull('task_timings.deleted_at')
            ->whereNull('task_projects.deleted_at')
            ->whereNotIn('tasks.department_id', [1, 6, 7, 8, 9, 10, 11])
            ->whereNotNull('tasks.department_id')
            ->when(!empty($requestDatas['start_time']), function ($query) use ($requestDatas) {
                $query->whereDate('task_timings.work_date', '>=', $requestDatas['start_time']);
            })
            ->when(!empty($requestDatas['end_time']), function ($query) use ($requestDatas) {
                $query->whereDate('task_timings.work_date', '<=', $requestDatas['end_time']);
            })
            ->when($isFilterProjectIds === true, function ($query) use ($requestDatas) {
                $query->whereIn('task_projects.project_id', $requestDatas['project_ids']);
            })
            ->when(!empty($requestDatas['department_ids']), function ($query) use ($requestDatas) {
                $query->whereIn('tasks.department_id', $requestDatas['department_ids']);
            })
            ->when(!empty($requestDatas['user_id']), function ($query) use ($requestDatas) {
                $query->where('tasks.user_id', $requestDatas['user_id']);
            })
            ->with('user')
            ->groupBy('tasks.id', 'project_weights.project_weight')
            ->get();

            // Get weight lost and weight added
            $weightedFluctuations = $this->getWeightedFluctuations($requestDatas, $isFilterProjectIds);
            $groups = collect($tasks)->groupBy('user_id');

            $summary = $groups->map(function ($employees, $employeeId) use ($weightedFluctuations, $departments) {
                $user = $employees->first()->user ?? null;
                $user_id = $user ? $user->id : null;

                $userFluctuations = $user_id ? $weightedFluctuations->where('user_id', $user_id)->first() : null;
                $totalWeightLost = $userFluctuations ? $userFluctuations->weights_lost : 0;
                $totalWeightAdded = $userFluctuations ? $userFluctuations->weights_added : 0;

                $totalWeightEmployee = $employees->sum(function ($employee) {
                    return $employee[$employee['project_weight'] > 0 ? 'project_weight' : 'task_weight'];
                });
                $totalWeightEmployeeCompleted = $employees->where('status', 4)->sum(function ($employee) {
                    return $employee[$employee['project_weight'] > 0 ? 'project_weight' : 'task_weight'];
                });
                $totalWeightEmployeeQuality = $employees->where('status', 4)->sum(function ($employee) {
                    return ($employee[$employee['project_weight'] > 0 ? 'project_weight' : 'task_weight'] * $employee['quality']) / 100;
                });
                // Add the weights lost and added to totalWeightEmployeeCompleted
                $totalWeightEmployeeCompleted += $totalWeightLost + $totalWeightAdded;
                $totalWeightEmployeeQuality += $totalWeightLost + $totalWeightAdded;
            
                $totalTasks = $employees->count();
                $totalCompleted = $employees->where('status', 4)->count();
                $percentCompleted = $totalTasks > 0 ? round($totalCompleted / $totalTasks * 100, 2) : 0;
            
                $percentWeightCompleted = $totalWeightEmployee > 0
                    ? round($totalWeightEmployeeCompleted / $totalWeightEmployee * 100, 2)
                    : 0;

                return [
                    'fullname' => $user ? $user->fullname : null,
                    'department_id' => $employees->first()->department_id,
                    'department_name' => $departments[$employees->first()->department_id],
                    'total' => $totalTasks,
                    'total_slow' => $employees->where('status', 0)->count(),
                    'total_wait' => $employees->where('status', 1)->count(),
                    'total_processing' => $employees->where('status', 2)->count(),
                    'total_pause' => $employees->where('status', 3)->count(),
                    'total_completed' => $totalCompleted,
                    'total_wait_fb' => $employees->where('status', 5)->count(),
                    'total_again' => $employees->where('status', 6)->count(),
                    'total_weight_employee' => round($totalWeightEmployee ?? 0, 2),
                    'total_weight_employee_completed' => round($totalWeightEmployeeCompleted ?? 0, 2),
                    'total_weight_employee_quality' => round($totalWeightEmployeeQuality ?? 0, 2),
                    'weights_added' => $totalWeightAdded,
                    'weights_lost' => $totalWeightLost,
                    'rate_task_completed' => $percentCompleted,
                    'rate_weight_completed' => $percentWeightCompleted
                ];
            })->values()->all();
            

            return response()->json($summary);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getWeightedFluctuations($requestDatas, $isFilterProjectIds)
    {
        $subquery = TaskTiming::select('task_id')
        ->whereNull('deleted_at')
        ->when(!empty($requestDatas['start_time']), function ($query) use ($requestDatas) {
            $query->whereDate('work_date', '>=', $requestDatas['start_time']);
        })
        ->when(!empty($requestDatas['end_time']), function ($query) use ($requestDatas) {
            $query->whereDate('work_date', '<=', $requestDatas['end_time']);
        })
        ->groupBy('task_id');

        $weights = WeightedFluctuation::selectRaw("
            weighted_fluctuations.user_id as user_id,
            SUM(CASE
                WHEN weighted_fluctuations.type IN (0, 1)
                THEN weighted_fluctuations.weight ELSE 0 END
            ) as weights_added,
            SUM(CASE
                WHEN weighted_fluctuations.type = 2
                THEN weighted_fluctuations.weight ELSE 0 END
            ) as weights_lost
        ")
        ->join('tasks', function ($join) {
            $join->on('tasks.id', '=', 'weighted_fluctuations.task_id')->whereNull('tasks.deleted_at');
        })
        ->joinSub($subquery, 'ttimings', function ($join) {
            $join->on('ttimings.task_id', '=', 'tasks.id');
        })
        ->when($isFilterProjectIds === true, function ($query) use ($requestDatas) {
            $query->join(DB::raw("
                (SELECT task_id
                FROM task_projects
                WHERE deleted_at IS NULL AND project_id IN (" . implode(',', $requestDatas['project_ids']) . ")
                GROUP BY task_id) as project_weights"), 'tasks.id', '=', 'project_weights.task_id'
            );
        })
        ->whereNotIn('tasks.department_id', [1, 6, 7, 8, 9, 10, 11])
        ->whereNotNull('tasks.department_id')
        ->when(!empty($requestDatas['department_ids']), function ($query) use ($requestDatas) {
            $query->whereIn('tasks.department_id', $requestDatas['department_ids']);
        })
        ->when(!empty($requestDatas['user_id']), function ($query) use ($requestDatas) {
            $query->where('weighted_fluctuations.user_id', $requestDatas['user_id']);
        })
        ->groupBy('weighted_fluctuations.user_id')
        ->get();

        return $weights;
    }

    public function getWorkdayReports(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();
            if (!isset($requestDatas['department_id'])) {
                $requestDatas['department_id'] = Auth()->user()->department_id;
            }

            $report = CommonController::getWorkTime($requestDatas);

            $requestDatas['timesheet_report'] = true;
            $requestDatas['type'] = 'workday_report';
            $employees = CommonController::getTimesheetReport($requestDatas);

            // Convert the arrays to Laravel Collections
            $collection1 = collect($report);
            $collection2 = collect($employees);
            
            // Use the mergeWith method to merge the 'worktimes' array
            $result = $collection2->map(function ($item2) use ($collection1) {
                $matchingItem = $collection1->firstWhere('id', $item2['id']);
                
                // if ($matchingItem) {
                    return $this->mapData($item2, $matchingItem);
                // }
                
                // return null; // Return null if no match is found
                // return $this->mapData($item2, null);
            });
            
            // Filter out null values (if there was no match)
            $result = $result->filter();
            
            // Convert the result back to an array
            $updatedArray2 = $result->values()->all();

            return response()->json($updatedArray2);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
        }
    }

    // Function to map the data
    private function mapData($item2, $matchingItem)
    {
        $timesheetsData = [];
        if ($matchingItem == null) { 
            $timesheetsData = [];
        } else {
            $timesheetsData = collect($matchingItem['worktimes'])
                ->map(function ($work, $dateYmd) use ($item2) {
                    $tasks = $work['time'];
    
                    // Check if the key exists in $item2['timesheets']
                    if (isset($item2['timesheets'][$dateYmd])) {
                        $timesheets = $this->calculateTimesheets($item2['timesheets'][$dateYmd]);
                    } else {
                        // Key does not exist in $item2['timesheets'], set 'timesheets' to 0
                        $timesheets = 0;
                    }
    
                    return [
                        'tasks' => $tasks,
                        'timesheets' => $timesheets,
                        'timesheet_detail' => [],
                    ];
                });
            $timesheetsData = $timesheetsData->all();
        }
        foreach ($item2['timesheets'] as $key => $value) {
            if (!isset($timesheetsData[$key])) {
                $timesheetsData[$key]['tasks'] = 0;
                $timesheetsData[$key]['timesheets'] = $this->calculateTimesheets($item2['timesheets'][$key]);
                $timesheetsData[$key]['timesheet_detail'] = $value;
            }
        }

        return [
            'id' => $item2['id'],
            'fullname' => $item2['fullname'],
            'worktime' => $timesheetsData,
        ];
    }

    private function calculateTimesheets($timesheet)
    {
        return round(
            (isset($timesheet['workday_original']) && $timesheet['workday_original'] > 0 ? $timesheet['workday_original']*8 : (isset($timesheet['workday']) ? $timesheet['workday']*8 : 0)) +
            (isset($timesheet['go_early_total']) && $timesheet['go_early_total'] > 0 ? ($timesheet['go_early_total'] / 60 / 60) : 0) +
            (isset($timesheet['leave_late_total']) && $timesheet['leave_late_total'] > 0 ? ($timesheet['leave_late_total'] / 60 / 60) : 0) +
            (isset($timesheet['extra_warrior_time']) && $timesheet['extra_warrior_time'] > 0 ? ($timesheet['extra_warrior_time'] / 60 / 60) : 0) -
            (isset($timesheet['non_office_time_goouts']) && $timesheet['non_office_time_goouts'] > 0 ? ($timesheet['non_office_time_goouts'] / 60 / 60) : 0),
            2 // Specify the number of decimal places to round to (2 in this case)
        );
    }

    /** Select boxes data
     *
     *
     * @group Report
     *
     *
     * @response 200 {
     *  [
     *      "departments": [
     *          "Dev,
     *          "Game Design",
     *          "Art",
     *          "Tester",
     *          "Phân tích dữ liệu"
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
    public function getSelectBoxes()
    {
        try {
            //list projects
            $projects = Project::query()->select('id', 'name')->orderBy('ordinal_number', 'asc')->get();
            //list departments
            $departments = CommonController::getDepartmentsJob();
            $dpIds = array_map(function ($department) {
                return $department['value'];
            }, $departments);
            $users = User::select('id', 'fullname')
                        ->whereIn('department_id', $dpIds)->get();

            //period time
            $datePeriod = [
                'date_start' => Carbon::now()->startOfMonth()->format("Y/m/d"),
                'date_end' => Carbon::now()->endOfMonth()->format('Y/m/d')
            ];

            $listBoxed = [
                'projects' => $projects,
                'users' => $users,
                'departments' => $departments,
                'date_period' => $datePeriod
            ];

            return response()->json($listBoxed);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getWorkdayReportSelboxes()
    {
        try {
            //list departments
            $departments = CommonController::getDepartmentsJob();
            //employees
            $employees = User::select('id', 'fullname', 'department_id')->get();
            //session
            $user = Auth()->user();
            $session = [
                'id' => $user->id,
                'department_id' => $user->department_id,
                'position' => $user->position,
            ];

            $listBoxed = [
                'users' => $employees,
                'departments' => $departments,
                'session' => $session
            ];

            return response()->json($listBoxed);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
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
    private function addSqlWithSorting($requestDatas, $where, $isUser)
    {
        //Change the SQL according to the requested search conditions
        if (!empty($requestDatas['project_ids'])) {
            $project_ids = join(",", $requestDatas['project_ids']);
            $where .= " and tasks.project_id IN (".$project_ids.")";
        }

        if (!empty($requestDatas['department_ids'])) {
            $department_ids = join(",", $requestDatas['department_ids']);
            $where .= " and tasks.department_id IN (".$department_ids.")";
        }

        if (!empty($requestDatas['start_time'])) {
            $start_time = Carbon::create($requestDatas['start_time'])->format("Y/m/d 00:00:00");
            $where .= " and tasks.start_time >= '".$start_time."'";
        }

        if (!empty($requestDatas['end_time'])) {
            $end_time = Carbon::create($requestDatas['end_time'])->format("Y/m/d 23:59:59");
            $where .= " and tasks.end_time <= '".$end_time."'";
        }

        if ($isUser) {
            if (!empty($requestDatas['fullname'])) {
                $where .= " and lower(users.fullname) LIKE '%".mb_strtolower($requestDatas['fullname'], 'UTF-8')."%'";
            }
        }

        return $where;
    }
}
