<?php

namespace App\Http\Controllers\api\Warrior;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Project;

use Carbon\Carbon;
use Storage;
use App\Http\Controllers\api\CommonController;
use App\Models\HolidayOffset;

/**
 * Employee API
 *
 * @group Employee
 */
class WarriorProjectController extends Controller
{
    public function getSelectboxes()
    {
        try {
            $user = Auth()->user();
            $userIdLogin = $user->id;
            $projects = Project::select('id', 'name')->orderBy('ordinal_number', 'asc')->get();
            $departments = config('const.departments');

            //userSelbox
            $users = User::query()
                ->select(DB::raw('users.id as id,
                                    users.fullname as fullname'))
                ->where('users.user_status', '!=', 2)
                ->where(function ($query) use ($user, $fullPermission) {
                    if ($user->position == 1) {
                        $query->where('department_id', $user->department_id);
                    } elseif (!in_array($user->id, $fullPermission)) {
                        $query->where('id', $user->id);
                    }
                })
                ->get();

            $data = [
                'projects' => $projects,
                'departments' => $departments,
                'users' => $users,
                'session' => [
                    'id' => $userIdLogin,
                    'department_id' => $user->department_id,
                    'position' => $user->position
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
    // public function getWarriorProject(Request $request)
    // {
    //     try {
    //         $requestDatas = $request->all();
    //         $timeSheets = CommonController::getTotalTimesheetReport($requestDatas);
    //         $users = User::select(
    //             'users.id',
    //             'users.fullname',
    //             'projects.name as project_name',
    //             'projects.id as project_id',
    //             DB::raw('sum(task_timings.time_spent) as sum_time')
    //         )
    //         ->join('tasks', function ($join) {
    //             $join->on('users.id', '=', 'tasks.user_id')->whereNull('tasks.deleted_at');
    //         })
    //         ->join('task_projects', function ($join) {
    //             $join->on('tasks.id', '=', 'task_projects.task_id')->whereNull('task_projects.deleted_at');
    //         })
    //         ->join('projects', function ($join) {
    //             $join->on('projects.id', '=', 'task_projects.project_id')->whereNull('projects.deleted_at');
    //         })
    //         ->join('task_timings', function ($join) {
    //             $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');
    //         })
    //         ->whereBetween('task_timings.work_date', [
    //             Carbon::parse($requestDatas['start_date'])->format('Y-m-d'),
    //             Carbon::parse($requestDatas['end_date'])->format('Y-m-d')
    //         ])
    //         ->whereNull('task_timings.task_assignment_id')
    //         ->whereNull('users.deleted_at')
    //         ->groupBy('users.id', 'users.fullname', 'projects.name', 'projects.id')
    //         ->orderByRaw('users.date_official asc, users.created_at asc')
    //         ->get();
        
    //         $users = $users->groupBy('id')->map(function ($user) {
    //             $result = [
    //                 'id' => $user[0]['id'],
    //                 'fullname' => $user[0]['fullname'],
    //                 'total_time' => 0,
    //             ];
            
    //             foreach ($user as $project) {
    //                 $result[$project['project_id']] = floatval(number_format($project['sum_time'], 2, '.', ''));
    //                 $result['total_time'] += $project['sum_time'];
    //             }
    //             $result['total_time'] = floatval(number_format($result['total_time'], 2, '.', ''));
    //             return $result;
    //         })->values()->all();

    //         $mergedData = collect($users)->merge($timeSheets)->groupBy(['id'])->map(function ($grouped) {
    //             $data = [];

    //             foreach ($grouped as $item) {
    //                 foreach ($item as $key => $value) {
    //                     $data[$key] = $value;
    //                 }
    //             }

    //             $data['go_early_sum'] = $data['go_early_sum'] ?? 0;
    //             $data['leave_late_sum'] = $data['leave_late_sum'] ?? 0;
    //             $data['extra_warrior_time'] = $data['extra_warrior_time'] ?? 0;
    //             $data['non_office_time_goouts'] = $data['non_office_time_goouts'] ?? 0;
    //             $data['origin_workday'] = $data['origin_workday'] ?? 0;
    //             $data['total_time_working'] = number_format(($data['go_early_sum'] + $data['leave_late_sum'] + $data['extra_warrior_time'] - $data['non_office_time_goouts']) / 3600 + $data['origin_workday'] * 8, 2, '.', '');
    //             return $data;
    //         })->values()->all();


    //         $columnTotals = [];
    //         $columnPercents = [];
    //         foreach ($mergedData as $userData) {
    //             foreach ($userData as $key => $value) {
    //                 if ($key !== 'id' && $key !== 'fullname' && $key !== 'date_official') {
    //                     if (!isset($columnTotals[$key])) {
    //                         $columnTotals[$key] = 0;
    //                     }
    //                     $columnTotals[$key] += $value;
    //                     $columnTotals[$key] = floatval(number_format($columnTotals[$key], 2, '.', ''));
    //                 }
    //             }
    //         }

    //         foreach ($columnTotals as $key => $total) {
    //             if ($key !== 'id' && $key !== 'fullname' && $key !== 'date_official' && $key !== 'total_time_working') {
    //                 if (!isset($columnPercents[$key])) {
    //                     $columnPercents[$key] = '0 %';
    //                 }
    //                 if (is_numeric($total) && $total != 0 && isset($columnTotals['total_time']) && $columnTotals['total_time'] != 0) {
    //                     $columnPercents[$key] = floatval(number_format( $total / $columnTotals['total_time'] * 100 , 2, '.', '')).' %';

    //                 } else {
    //                     $columnPercents[$key] = '0 %';
    //                 }
    //             }
    //         }

    //         $columnTotals['id'] = 'Total';
    //         $columnTotals['fullname'] = 'Total';
    //         $columnPercents['id'] = 'Percent';
    //         $columnPercents['fullname'] = 'Percent';
    //         array_unshift($mergedData, $columnPercents);
    //         array_unshift($mergedData, $columnTotals);

    //         $projects = Project::select(
    //             'projects.name as project_name',
    //             'projects.id as project_id',
    //             'projects.project_parent_time',
    //             DB::raw('sum(task_timings.time_spent) as sum_time')
    //         )
    //         ->join('task_projects', function ($join) {
    //             $join->on('projects.id', '=', 'task_projects.project_id')->whereNull('task_projects.deleted_at');
    //         })
    //         ->join('tasks', function ($join) {
    //             $join->on('tasks.id', '=', 'task_projects.task_id')->whereNull('tasks.deleted_at');
    //         })
    //         ->join('task_timings', function ($join) {
    //             $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');
    //         })
    //         ->whereBetween('task_timings.work_date', [
    //             Carbon::parse($requestDatas['start_date'])->format('Y-m-d'),
    //             Carbon::parse($requestDatas['end_date'])->format('Y-m-d')
    //         ])
    //         ->whereNull('task_timings.task_assignment_id')
    //         ->groupBy('projects.name', 'projects.id','projects.project_parent_time')
    //         ->get();
    //         if (count($projects) === 0) {
    //             return response()->json([
    //                 'status' => Response::HTTP_NOT_FOUND,
    //                 'errors' => __('MSG-E-003')
    //             ], Response::HTTP_NOT_FOUND);
    //         }

    //         $project_columns = clone $projects;
            
    //         $totalSumTime = $projects->sum('sum_time');
    //         $totalProject = [
    //             'project_id' => 'Total',
    //             'project_name' => 'Total',
    //             'sum_time' => floatval(number_format($totalSumTime, 2, '.', '')),
    //             'percent' => 0,
    //             'project_salary' => 0,
    //             'general_expenses' => 0,
    //             'total_cost' => 0,
    //             'percent_cost' => 0,
    //         ];
    //         $costProject = [
    //             'project_id' => 9999999,
    //             'project_name' => 'Chi phí chung',
    //         ];
    //         foreach ($projects as $project) {
    //             $percent = $totalSumTime != 0 ? ($project->sum_time / $totalSumTime) * 100 : 0;
    //             $project->percent = number_format($percent, 8, '.', '');
    //             $project->sum_time = floatval(number_format($project->sum_time, 2, '.', ''));

    //             $totalProject['percent'] += $project->percent;
    //             $totalProject['percent'] = number_format($totalProject['percent'], 8, '.', '');
    //         }
    //         $project_parents = $projects->map(function ($item) {
    //             return $item->replicate()->setRawAttributes($item->getAttributes());
    //         });

    //         $getProjectParent = $this->getProjectParent($project_parents, $totalSumTime);
    //         $project_parents = $getProjectParent['project_parents'];
    //         $offsetGeneralExpenses = $getProjectParent['project_offset'];
            

    //         foreach($project_parents as $key => $value) {
    //             if (isset($requestDatas['general_expenses'])){
    //                 $value['general_expenses'] = ($requestDatas['general_expenses'] + $offsetGeneralExpenses) * $value['percent'] / 100;
    //                 $value['general_expenses'] = floatval(number_format($value['general_expenses'],0,'.',''));
    //                 $totalProject['general_expenses'] += $value['general_expenses'];
    //                 $value['total_cost'] = $value['general_expenses'];
    //                 $totalProject['total_cost'] += $value['total_cost'];
    //             }
    //         }
    //         foreach($project_parents as $key => $value) {
    //             if (isset($requestDatas['general_expenses'])){
    //                 $value['percent_cost'] = $value['total_cost'] > 0 ? $value['total_cost']/$totalProject['total_cost']*100 : 0;
    //                 $value['percent_cost'] = number_format($value['percent_cost'],2,'.','');
    //                 $totalProject['percent_cost'] += $value['percent_cost'];
    //                 $totalProject['percent_cost'] = floatval(number_format($totalProject['percent_cost'], 2, '.', ''));
    //             }
    //         }
    //         $projects->prepend((object)$totalProject);

    //         $totalProject['sum_time'] = $getProjectParent['sum_time'];
    //         $projectSelect = $project_parents->map(function ($item) {
    //             return $item->replicate()->setRawAttributes($item->getAttributes());
    //         });
    //         $projectSelect->prepend((object)$costProject);
    //         $project_parents->prepend((object)$totalProject);

    //         $projects = json_decode(json_encode($projects), true);
    //         $projectSelect = json_decode(json_encode($projectSelect), true);
    //         foreach ($projects as $key => $value) {
    //             $projects[$key]['project_parent_name'] = '';
    //             foreach ($projectSelect as $key_parent => $value_parent) {
    //                 if (isset($value['project_parent_time']) && $value['project_parent_time'] == $value_parent['project_id']) {
    //                     $projects[$key]['project_parent_name'] = $value_parent['project_name'];
    //                 }
    //             }
    //         }

    //         $data = [
    //             'users' => $mergedData,
    //             'project_columns' => $project_columns,
    //             'projects' => $projects,
    //             'project_parents' => $project_parents,
    //             'total_sum_time' => $totalSumTime,
    //             'project_select' => $projectSelect,
    //         ];
    //         return response()->json($data);
    //     } catch (Exception $e) {
    //         Log::error($e);
    //         return response()->json([
    //             'status' => Response::HTTP_NOT_FOUND ,
    //             'errors' => $e->getMessage(),
    //             ], Response::HTTP_NOT_FOUND);
    //     }
    // }
    // public function getWarriorProject(Request $request)
    // {
    //     try {
    //         $requestDatas = $request->all();
            
    //         $projects = Project::select(
    //             'task_timings.work_date',
    //             'projects.name as project_name',
    //             'projects.id as project_id',
    //             'projects.project_parent_time',
    //             'tasks.name',
    //             'tasks.user_id',
    //             'users.fullname',
    //             DB::raw('max(task_timings.work_date) as max_work_date'),
    //             DB::raw('min(task_timings.work_date) as min_work_date'),
    //             DB::raw('sum(task_timings.time_spent) as actual_time')
    //         )
    //         ->join('task_projects', function ($join) {
    //             $join->on('projects.id', '=', 'task_projects.project_id')->whereNull('task_projects.deleted_at');
    //         })
    //         ->join('tasks', function ($join) {
    //             $join->on('tasks.id', '=', 'task_projects.task_id')->whereNull('tasks.deleted_at');
    //         })
    //         ->join('users', function ($join) {
    //             $join->on('users.id', '=', 'tasks.user_id')->whereNull('users.deleted_at');
    //         })
    //         ->join('task_timings', function ($join) {
    //             $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');
    //         })
    //         ->whereNull('task_timings.task_assignment_id')
    //         ->groupBy(
    //             'projects.name', 
    //             'projects.id',
    //             'projects.project_parent_time',
    //             'tasks.user_id',
    //             'tasks.name',
    //             'task_timings.work_date',
    //             'users.fullname'
    //         );

    //         if ($requestDatas['project_id']) {
    //             $projects->whereIn('projects.id', $requestDatas['project_id']);
    //         }
    //         if ($requestDatas['user_id']) {
    //             $projects->whereIn('users.id', $requestDatas['user_id']);
    //         }
    //         if (isset($requestDatas['start_date']) && isset($requestDatas['end_date'])) {
    //             $projects->whereBetween('task_timings.work_date', [
    //                 Carbon::parse($requestDatas['start_date'])->format('Y-m-d'),
    //                 Carbon::parse($requestDatas['end_date'])->format('Y-m-d')
    //             ]);
    //         }
    //         $projects = $projects->get();

    //         $timeSheets = CommonController::getTimesheetReport($requestDatas);
            
    //         if (count($projects) === 0) {
    //             return response()->json([
    //                 'status' => Response::HTTP_NOT_FOUND,
    //                 'errors' => __('MSG-E-003')
    //             ], Response::HTTP_NOT_FOUND);
    //         }

    //         $data = [
    //             'projects' => $projects,
    //         ];
    //         return response()->json($data);
    //     } catch (Exception $e) {
    //         Log::error($e);
    //         return response()->json([
    //             'status' => Response::HTTP_NOT_FOUND ,
    //             'errors' => $e->getMessage(),
    //             ], Response::HTTP_NOT_FOUND);
    //     }
    // }

    public function getWarriorProject(Request $request)
    {
        try {
            ini_set('memory_limit', '2048M');
            $requestDatas = $request->all();
            
            $subQuery = Project::join('task_projects', function ($join) {
                $join->on('projects.id', '=', 'task_projects.project_id')->whereNull('task_projects.deleted_at');
            })
            ->join('tasks', function ($join) {
                $join->on('tasks.id', '=', 'task_projects.task_id')->whereNull('tasks.deleted_at');
            })
            ->join('task_timings', function ($join) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');
            })
            ->select(
                'projects.id as project_id',
                'projects.name as project_name',
                DB::raw('MAX(task_timings.work_date) as max_work_date'),
                DB::raw('MIN(task_timings.work_date) as min_work_date')
            )
            ->groupBy('projects.id');
            if (isset($requestDatas['project_id'])) {
                $subQuery->whereIn('projects.id', $requestDatas['project_id']);
            }
            $projects = Project::select(
                'task_timings.work_date',
                'projects.name as project_name',
                'projects.id as project_id',
                'projects.project_parent_time',
                'tasks.name',
                'tasks.user_id',
                'users.fullname',
                'sub.min_work_date',
                'sub.max_work_date',
                DB::raw('SUM(task_timings.time_spent) as actual_time'),
            )
            ->join('task_projects', function ($join) {
                $join->on('projects.id', '=', 'task_projects.project_id')->whereNull('task_projects.deleted_at');
            })
            ->join('tasks', function ($join) {
                $join->on('tasks.id', '=', 'task_projects.task_id')->whereNull('tasks.deleted_at');
            })
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'tasks.user_id')->whereNull('users.deleted_at');
            })
            ->join('task_timings', function ($join) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');
            })
            ->join(DB::raw('(' . $subQuery->toSql() . ') as sub'), function ($join) {
                $join->on('projects.id', '=', 'sub.project_id');
            })
            ->mergeBindings($subQuery->getQuery()) // Merge bindings from subquery
            ->whereNull('task_timings.task_assignment_id')
            ->groupBy(
                'projects.name', 
                'projects.id',
                'projects.project_parent_time',
                'tasks.user_id',
                'tasks.name',
                'task_timings.work_date',
                'users.fullname',
                'sub.max_work_date',
                'sub.min_work_date'
            );
            
            if (isset($requestDatas['project_id']) && count($requestDatas['project_id']) > 0) {
                $projects->whereIn('projects.id', $requestDatas['project_id']);
            }
            if (isset($requestDatas['user_id'])  && count($requestDatas['user_id']) > 0) {
                $projects->whereIn('users.id', $requestDatas['user_id']);
            }
            if (isset($requestDatas['department_id'])  && count($requestDatas['department_id']) > 0) {
                $projects->whereIn('users.department_id', $requestDatas['department_id']);
            }
            if (isset($requestDatas['start_date']) && isset($requestDatas['end_date'])) {
                $projects->whereBetween('task_timings.work_date', [
                    Carbon::parse($requestDatas['start_date'])->format('Y-m-d'),
                    Carbon::parse($requestDatas['end_date'])->format('Y-m-d')
                ]);
            }
            
            $projects = $projects->get();
            $timeSheets = [];
            if (count($projects) > 0) {
                // $requestDatas['start_date'] = $projects[0]['min_work_date'];
                // $requestDatas['end_date'] = $projects[0]['max_work_date'];
                $requestDatas['start_date'] = isset($requestDatas['start_date']) ? $requestDatas['start_date'] : min(array_column($subQuery->get()->toArray(), 'min_work_date'));
                $requestDatas['end_date'] = isset($requestDatas['end_date']) ? $requestDatas['end_date'] : max(array_column($subQuery->get()->toArray(), 'max_work_date'));
                unset($requestDatas['project_id']);
                $timeSheets = CommonController::getTimesheetReport($requestDatas);
            } else {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
            }
            

            $dataWarrior = [];

            foreach ($projects as $project) {
                $userId = $project['user_id'];
                // $workDate = str_replace('-', '', $project['work_date']);
                $workDate =  Carbon::parse($project['work_date'])->format('Ymd');
        
                // Find the corresponding timesheet for user_id and work_date
                foreach ($timeSheets as $timesheet) {
                    if ($timesheet['id'] == $userId) {
                        if (isset($timesheet['timesheets'][$workDate])) {
                            $workingTime = $timesheet['timesheets'][$workDate];

                            if (isset($workingTime['check_in']) && isset($workingTime['check_out'])) {
                                $checkIn = Carbon::parse($workingTime['check_in']);
                                $checkOut = Carbon::parse($workingTime['check_out']);

                                $workingTime['time_working'] = 
                                    $checkOut->diffInSeconds($checkIn) - 
                                    (!Carbon::parse((string)$workDate)->isSaturday() ? 1.5*60*60 : 0) - 
                                    (isset($workingTime['non_office_time_goouts']) ? $workingTime['non_office_time_goouts'] : 0 ) - 
                                    (isset($workingTime['office_time_goouts']) ? $workingTime['office_time_goouts'] : 0 ) 
                                ;
                                $workingTime['time_working'] = $workingTime['time_working'] / 3600;

                                $workingTime['time_warrior'] = 
                                    (isset($workingTime['go_early_total']) ? $workingTime['go_early_total'] : 0 ) + 
                                    (isset($workingTime['leave_late_total']) ? $workingTime['leave_late_total'] : 0 ) -
                                    (isset($workingTime['non_office_time_goouts']) ? $workingTime['non_office_time_goouts'] : 0 )
                                ;
                                $workingTime['time_warrior'] = $workingTime['time_warrior'] / 3600;

                                $workingTime['t'] = $workingTime['time_working'] > 0 ? $project['actual_time'] / $workingTime['time_working'] : 0;
                                $workingTime['x'] = $workingTime['time_warrior'] > 0 ? $workingTime['t'] * $workingTime['time_warrior'] : 0;

                            }

                            $dataWarrior[] = array_merge($project->toArray(), $workingTime);
                        } else {
                            $dataWarrior[] = $project->toArray();
                        }
                    }
                }
            }
            // return response()->json($dataWarrior);
            
            // $dataWarriorTotal = [];
            // foreach ($dataWarrior as $key => $value) {
            //     $keyNew = $value['user_id'] . '_' . $value['project_id']. '_' . $value['project_name'];
            //     if (!isset($dataWarriorTotal[$keyNew])) {
            //         $dataWarriorTotal[$keyNew]['project_id'] = $value['project_id'];
            //         $dataWarriorTotal[$keyNew]['project_name'] = $value['project_name'];
            //         $dataWarriorTotal[$keyNew]['user_id'] = $value['user_id'];
            //         $dataWarriorTotal[$keyNew]['fullname'] = $value['fullname'];

            //         $dataWarriorTotal[$keyNew]['T'] = 0;
            //         $dataWarriorTotal[$keyNew]['X'] = 0;
            //         isset($value['t']) ? $dataWarriorTotal[$keyNew]['T'] = $value['t'] : '';
            //         isset($value['x']) ? $dataWarriorTotal[$keyNew]['X'] = $value['x'] : '';
            //     } else if (isset($dataWarriorTotal[$keyNew])) {
            //         // $dataWarriorTotal[$keyNew]['T'] += $value['t'];
            //         // $dataWarriorTotal[$keyNew]['X'] += $value['x'];
            //         isset($value['t']) ? $dataWarriorTotal[$keyNew]['T'] += $value['t'] : '';
            //         isset($value['x']) ? $dataWarriorTotal[$keyNew]['X'] += $value['x'] : '';
            //         unset($dataWarriorTotal[$keyNew]['t']);
            //         unset($dataWarriorTotal[$keyNew]['x']);
            //     }
            //     // var_dump($dataWarriorTotal);
            // }
            // // Convert back to array
            // $dataWarriorTotal = array_values($dataWarriorTotal);

            $dataWarriorTotal = [];

            foreach ($dataWarrior as $key => $value) {
                $userId = $value['user_id'];
                $fullname = $value['fullname'];
                $projectId = $value['project_id'];
                $projectName = $value['project_name'];
                $T = isset($value['t']) ? $value['t'] : 0;
                $X = isset($value['x']) ? $value['x'] : 0;

                if (!isset($dataWarriorTotal[$userId])) {
                    $dataWarriorTotal[$userId] = [
                        'user_id' => $userId,
                        'fullname' => $fullname,
                        'projects' => []
                    ];
                }
                $requestDataTotal = [
                    'user_id' => $userId,
                    // 'start_date' => Carbon::parse($value['min_work_date'])->format('Y/m/d'),
                    // 'end_date' => Carbon::parse($value['max_work_date'])->format('Y/m/d'),
                    'start_date' => isset($requestDatas['start_date']) ? Carbon::parse($requestDatas['start_date'])->format('Y/m/d') : Carbon::parse(min(array_column($subQuery->get()->toArray(), 'min_work_date')))->format('Y/m/d'),
                    'end_date' => isset($requestDatas['end_date']) ? Carbon::parse($requestDatas['end_date'])->format('Y/m/d') : Carbon::parse(max(array_column($subQuery->get()->toArray(), 'max_work_date')))->format('Y/m/d')
                ];
                if (!isset($dataWarriorTotal[$userId]['projects'][$projectId])) {
                    $totalWorkdate = CommonController::getTotalTimesheetReport($requestDataTotal)[0];
                    $dataWarriorTotal[$userId]['projects'][$projectId] = [
                        'min_work_date' => $value['min_work_date'],
                        'max_work_date' => $value['max_work_date'],
                        'totalLeave' =>  $totalWorkdate['paid_leave'] + $totalWorkdate['un_paid_leave'],
                        'totalWork' =>  $totalWorkdate['origin_workday'] + $totalWorkdate['paid_leave'],
                        'percentLeave' => ($totalWorkdate['paid_leave'] + $totalWorkdate['un_paid_leave']) / ($totalWorkdate['origin_workday'] + $totalWorkdate['paid_leave']) * 100,
                        // 'totalLeave' =>  CommonController::getTotalTimesheetReport($requestDataTotal)[0],
                        'T' => $T,
                        'X' => $X,
                        'W' => $this->getWarriorTitle($X,$T)
                    ];
                } else {
                    $dataWarriorTotal[$userId]['projects'][$projectId]['T'] += $T;
                    $dataWarriorTotal[$userId]['projects'][$projectId]['X'] += $X;
                    $dataWarriorTotal[$userId]['projects'][$projectId]['W'] = $this->getWarriorTitle($dataWarriorTotal[$userId]['projects'][$projectId]['X'],$dataWarriorTotal[$userId]['projects'][$projectId]['T']);
                }
            }

            // Convert the associative array to a sequential array
            $dataWarriorTotal = array_values($dataWarriorTotal);

            // $dataTimesheetTotal = [];
            // foreach ($subQuery->get()->toArray() as $key => $value) {
            //     // $requestDataTotal = [
            //     //     'start_date' => Carbon::parse($value['min_work_date'])->format('Y/m/d'),
            //     //     'end_date' => Carbon::parse($value['max_work_date'])->format('Y/m/d'),
            //     // ];
            //     $requestDatas['start_date'] = isset($requestDatas['start_date']) ? $requestDatas['start_date'] : min(array_column($subQuery->get()->toArray(), 'min_work_date'));
            //     $requestDatas['end_date'] = isset($requestDatas['end_date']) ? $requestDatas['end_date'] : max(array_column($subQuery->get()->toArray(), 'max_work_date'));
            //     $dataTimesheetTotal[$value['project_id']][] = CommonController::getTotalTimesheetReport($requestDatas);
            // }
            // dd($dataTimesheetTotal);
            $data = [
                'projects' => $subQuery->get()->toArray(),
                'dataWarriorTotal' => $dataWarriorTotal,
            ];
            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getWarriorTitle($X, $T)
    {
        $currentDate = Carbon::now();
        $userDateOfficial = Auth()->user()->date_official;

        $diffInYears = $userDateOfficial ? $currentDate->diffInYears($userDateOfficial) : 0;


        $warriorMultiplier = ($diffInYears >= 3) ? 1 : 2;

        $level1 = $T * $warriorMultiplier;
        $level2 = $T * ($warriorMultiplier + 1);
        $level3 = $T * ($warriorMultiplier + 2);
        if ($X > 0 && $T > 0) {
            if ($X >= $level1 && $X < $level2) {
                return 'Warrior 1';
            } elseif ($X >= $level2 && $X < $level3) {
                return 'Warrior 2';
            } elseif ($X >= $level3) {
                return 'Warrior 3';
            }
        }
        return 'Soldier';
    }

    public function export(Request $request)
    {
        try {
            $requestDatas = $request->all();
            $templatePath = resource_path('templates/working_time_template.xlsx');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
            //set value
            $response = $this->getImportData($request);
            if (isset($response->original['status']) && $response->original['status'] == 404) {
                return response()->json([
                    'status' => $response->original['status'],
                    'errors' => $response->original['errors'],
                ], Response::HTTP_NOT_FOUND);
            }
            $data = $response->original;
            $this->fillData($spreadsheet, $data);
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            $filename = 'working_time_'.time().'.xlsx';
            $filePath = Storage::path('excels/'.$filename);

            //Save file
            $writer->save($filePath);

            return response()->download($filePath);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    private function fillData($spreadsheet, $data)
    {
        $columns = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
        ];
        $row = 2;

        $worksheet1 = $spreadsheet->getSheetByName('chi_phi_luong');
        $worksheet2 = $spreadsheet->getSheetByName('ti_le_du_an');
        $worksheet3 = $spreadsheet->getSheetByName('thong_ke');

        $data = json_decode(json_encode($data), true);
        foreach ($data['projects'] as $key => $value) {
            $this->setExcelData($worksheet2, 'A', $key+$row, $key+1);
            $this->setExcelData($worksheet2, 'B', $key+$row, $value['project_name']);
            $this->setExcelData($worksheet2, 'C', $key+$row, $value['sum_time'], 'n');
            $this->setExcelData($worksheet2, 'D', $key+$row, $value['percent'].'%');
            $this->setExcelData($worksheet2, 'E', $key+$row, isset($value['project_parent_name']) ? $value['project_parent_name'] : '');
        }

        foreach ($data['project_parents'] as $key => $value) {
            $this->setExcelData($worksheet3, 'H', 1, $data['project_parents'][0]['general_expenses_entered']);
            $this->setExcelData($worksheet3, 'A', $key+$row+1, $key+1);
            $this->setExcelData($worksheet3, 'B', $key+$row+1, $value['project_name']);
            $this->setExcelData($worksheet3, 'C', $key+$row+1, $value['sum_time'], 'n');
            $this->setExcelData($worksheet3, 'D', $key+$row+1, $value['percent'].'%');
            $this->setExcelData($worksheet3, 'E', $key+$row+1, $value['project_salary'], 'n');
            $this->setExcelData($worksheet3, 'F', $key+$row+1, $value['general_expenses'], 'n');
            $this->setExcelData($worksheet3, 'G', $key+$row+1, $value['total_cost'], 'n');
            $this->setExcelData($worksheet3, 'H', $key+$row+1, $value['percent_cost'].'%');
        }
        foreach ($data['users'] as $key => $value) {
            foreach ($data['project_columns'] as $keyData => $columnData) {
                $this->setExcelData($worksheet1, $columns[$keyData+2], 1, $columnData['project_name']);
                $data['project_columns'][$keyData]['col'] = $columns[$keyData+2];
                $endCol = $columns[$keyData+3];
            }
            $this->setExcelData($worksheet1, $endCol, 1, 'Tổng lương');
            $this->setExcelData($worksheet1, 'A', $key+$row, $key+1);
            $this->setExcelData($worksheet1, 'B', $key+$row, $value['fullname']);
            foreach ($data['project_columns'] as $keyData => $columnData) {
                if (isset($value[$columnData['project_id']])) {
                    $this->setExcelData($worksheet1, $columnData['col'], $key+$row, $value[$columnData['project_id']], 'n');
                }
            }
            $this->setExcelData($worksheet1, $endCol, $key+$row, $value['salary'], 'n');
        }
    }

    private function setExcelData($worksheet, $col, $row, $data, $type_string = "s",$size=10,$bold=false)
    {
        $worksheet->getCell($col . $row)->setValueExplicit($data, $type_string);
        $style = $worksheet->getStyle($col . $row);
        $style->getFont()->setName('Calibri')->getColor()->setRGB('000000');
        $style->getFont()->setSize($size)->setBold($bold);
    }

}
