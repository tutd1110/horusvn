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
use App\Models\User;
use App\Models\TaskAssignment;
use App\Models\TaskTiming;
use App\Models\Task;
use App\Models\TaskStatusLog;
use App\Models\Project;
use Carbon\Carbon;
use App\Http\Requests\api\Task\TaskAssignment\GetTaskAssignmentList;
use App\Http\Requests\api\Task\TaskAssignment\TaskAssignmentRegisterRequest;
use App\Http\Requests\api\Task\TaskAssignment\TaskAssignmentEditRequest;
use App\Http\Requests\api\Task\TaskAssignment\TaskAssignmentDeleteRequest;

/**
 * Task Assignment API
 *
 * @group Task Assignment
 */
class TaskAssignmentController extends Controller
{
    public function getDSelfCreatedSelBox()
    {
        try {
            $data = $this->getIssueSelbox();

            $session = Auth()->user();
            $tasks = Task::join('task_assignments', function ($join) {
                            $join->on(
                                'task_assignments.task_id',
                                '=',
                                'tasks.id'
                            )->whereNull('task_assignments.deleted_at');
                        })
                        ->select('tasks.id as value', 'tasks.name as label')
                        ->distinct()
                        ->get();


            $countDAssigned = TaskAssignment::join('users', 'users.id', '=', 'task_assignments.assigned_user_id')
                                    // ->where('users.department_id', $session->department_id)
                                    ->whereIn('status', [0,1])->count();

            $editors = config('const.task_assignments_edit_role');
            $subLeader = config('const.employee_add_permission');
            $session = [
                'id' => $session->id,
                'department_id' => $session->department_id,
                'editable' => in_array($session->id, $editors) || $session->position > 0 ? true : false,
                'subLeader' => in_array($session->id, $subLeader) ? true : false
            ];

            $data['count_d_assigned'] = $countDAssigned;
            $data['tasks'] = $tasks;
            $data['session'] = $session;

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getDAssignedSelBox()
    {
        try {
            $data = $this->getIssueSelbox();

            $session = Auth()->user();
            $tasks = Task::join('task_assignments', function ($join) {
                            $join->on(
                                'task_assignments.task_id',
                                '=',
                                'tasks.id'
                            )->whereNull('task_assignments.deleted_at');
                        })
                        ->where('tasks.department_id', $session->department_id)
                        ->select('tasks.id as value', 'tasks.name as label')
                        ->distinct()
                        ->get();


            $countDSelfCreated = TaskAssignment::whereIn('status', [0,1])->count();
                                    // when(!in_array($session->id, [51,107]) && $session->department_id != 12, function ($query) use ($session) {
                                    //     $query->join('users', 'users.id', '=', 'task_assignments.tester_id')
                                    //     ->where('users.department_id', $session->department_id);
                                    // })
                                    // ->whereIn('status', [0,1])->count();

            $editors = config('const.task_assignments_edit_role');
            $session = [
                'id' => $session->id,
                'department_id' => $session->department_id,
                'editable' => in_array($session->id, $editors) || $session->position > 0 ? true : false
            ];

            $data['count_d_self_created'] = $countDSelfCreated;
            $data['tasks'] = $tasks;
            $data['session'] = $session;

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getPSelfCreatedSelBox()
    {
        try {
            $data = $this->getIssueSelbox();


            $session = Auth()->user();
            $tasks = Task::join('task_assignments', function ($join) {
                            $join->on(
                                'task_assignments.task_id',
                                '=',
                                'tasks.id'
                            )->whereNull('task_assignments.deleted_at');
                        })
                        ->select('tasks.id as value', 'tasks.name as label')
                        ->distinct()
                        ->get();
            
            $countPAssigned = TaskAssignment::where('assigned_user_id', $session->id)->whereIn('status', [0,1])->count();

            $data['count_p_assigned'] = $countPAssigned;
            $data['tasks'] = $tasks;
    
            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getPAssignedSelBox()
    {
        try {
            $data = $this->getIssueSelbox();

            $session = Auth()->user();
            $tasks = Task::join('task_assignments', function ($join) {
                            $join->on(
                                'task_assignments.task_id',
                                '=',
                                'tasks.id'
                            )->whereNull('task_assignments.deleted_at');
                        })
                        ->where('tasks.department_id', $session->department_id)
                        ->select('tasks.id as value', 'tasks.name as label')
                        ->distinct()
                        ->get();


            $countPSelfCreated = TaskAssignment::where('tester_id', $session->id)->whereIn('status', [0,1])->count();
            $editors = config('const.task_assignments_edit_role');
            $session = [
                'id' => $session->id,
                'department_id' => $session->department_id,
                'editable' => in_array($session->id, $editors) ? true : false
            ];

            $data['count_p_self_created'] = $countPSelfCreated;
            $data['tasks'] = $tasks;
            $data['session'] = $session;

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getDSelfCreatedIssues(GetTaskAssignmentList $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $select = "task_assignments.id as id,
                task_assignments.project_id as project_id,
                task_assignments.tester_id as tester_id,
                task_assignments.task_id as task_id,
                task_assignments.task_id as task_id_input,
                task_assignments.start_date as start_date,
                task_assignments.description as description,
                task_assignments.assigned_department_id as assigned_department_id,
                task_assignments.assigned_user_id as assigned_user_id,
                task_assignments.status as status,
                task_assignments.tag_test as tag_test,
                task_assignments.note as note,
                task_assignments.level as level,
                task_assignments.type as type,
                task_assignments.updated_at as updated_at,
                task_timings.weight as weight,
                COUNT(task_assignment_comments.id) as comment_count";

            $issues = $this->getIssues($requestDatas, $select, 'd-self-created');

            //no search results
            if (count($issues) === 0) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json($issues);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getDAssignedIssues(GetTaskAssignmentList $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $select = "task_assignments.id as id,
                users.fullname as tester,
                task_assignments.project_id as project_id,
                task_assignments.task_id as task_id,
                task_assignments.task_id as task_id_input,
                task_assignments.start_date as start_date,
                task_assignments.assigned_user_id as assigned_user_id,
                task_assignments.description as description,
                task_assignments.status as status,
                task_assignments.tag_test as tag_test,
                task_assignments.note as note,
                task_assignments.level as level,
                task_assignments.type as type,
                task_assignments.updated_at as updated_at,
                COUNT(task_assignment_comments.id) as comment_count";

            $issues = $this->getIssues($requestDatas, $select, 'd-assigned');

            //no search results
            if (count($issues) === 0) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json($issues);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getPSelfCreatedIssues(GetTaskAssignmentList $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $select = "task_assignments.id as id,
                task_assignments.project_id as project_id,
                task_assignments.task_id as task_id,
                task_assignments.task_id as task_id_input,
                task_assignments.start_date as start_date,
                task_assignments.description as description,
                task_assignments.assigned_department_id as assigned_department_id,
                task_assignments.assigned_user_id as assigned_user_id,
                task_assignments.status as status,
                task_assignments.tag_test as tag_test,
                task_assignments.note as note,
                task_assignments.level as level,
                task_assignments.type as type,
                task_assignments.updated_at as updated_at,
                COUNT(task_assignment_comments.id) as comment_count";

            $issues = $this->getIssues($requestDatas, $select, 'p-self-created');

            //no search results
            if (count($issues) === 0) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json($issues);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getPAssignedIssues(GetTaskAssignmentList $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $select = "task_assignments.id as id,
                users.fullname as tester,
                task_assignments.project_id as project_id,
                task_assignments.task_id as task_id,
                task_assignments.task_id as task_id_input,
                task_assignments.start_date as start_date,
                task_assignments.description as description,
                task_assignments.status as status,
                task_assignments.tag_test as tag_test,
                task_assignments.note as note,
                task_assignments.level as level,
                task_assignments.type as type,
                task_assignments.updated_at as updated_at,
                COUNT(task_assignment_comments.id) as comment_count";

            $issues = $this->getIssues($requestDatas, $select, 'p-assigned');

            //no search results
            if (count($issues) === 0) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json($issues);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getIssueSelbox()
    {
        try {
            $status = config('const.task_assignments_status');
            $tagTests = config('const.task_assignments_tag_test');
            $levels = config('const.task_assignments_level');
            $types = config('const.task_assignment_type');
            $departments = CommonController::getDepartmentsJob();

            $users = User::select('id as value', 'fullname as label', 'department_id')
                        ->where('position', '!=', 3)->where('user_status', 1)
                        ->get();
            $projects = Project::select('id as value', 'name as label')->orderBy('ordinal_number', 'asc')->get();

            $data = [
                'status' => $status,
                'tag_tests' => $tagTests,
                'levels' => $levels,
                'types' => $types,
                'departments' => $departments,
                'projects' => $projects,
                'users' => $users
            ];
    
            return $data;
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getIssues($requestDatas, $select, $type)
    {
        try {
            $user = Auth()->user();
            $typeConditions = [
                'd-self-created' => 'users.department_id',
                'd-assigned' => 'task_assignments.assigned_department_id',
                'p-self-created' => 'task_assignments.tester_id',
                'p-assigned' => 'task_assignments.assigned_user_id',
            ];
            $query = TaskAssignment::leftJoin('users', 'users.id', '=', 'task_assignments.tester_id')
            ->leftJoin('task_assignment_comments', function ($join) {
                $join->on(
                    'task_assignment_comments.task_assignment_id',
                    '=',
                    'task_assignments.id'
                )->whereNull('task_assignment_comments.deleted_at');
            })->when(isset($requestDatas['weighted']), function ($query) {
                $query->join('task_timings', function ($join) {
                    $join->on('task_timings.task_assignment_id', '=', 'task_assignments.id')->whereNull('task_timings.deleted_at');
                });
            }, function ($query) {
                $query->leftJoin('task_timings', function ($join) {
                    $join->on('task_timings.task_assignment_id', '=', 'task_assignments.id')->whereNull('task_timings.deleted_at');
                });
            })
            
            ->selectRaw($select);
            // Check if the type match with typeConditions and set the condition accordingly
            // if ($type === 'd-self-created') {
            //     if (!in_array($user->id, [51,107]) && $user->department_id != 5) {
            //         $query->where($typeConditions[$type], $user->department_id);
            //     }
            // } else
            if ($type === 'd-assigned') {
                $query->where($typeConditions[$type], $user->department_id);
            } elseif (in_array($type, ['p-self-created', 'p-assigned'])) {
                $query->where($typeConditions[$type], $user->id);
            }
            if (isset($requestDatas['type']) && !empty($requestDatas['type'])) {
                $query->whereIn('task_assignments.type', $requestDatas['type']);
            }

            //Add SQL according to requested search conditions
            if (!empty($requestDatas)) {
                //get json from request
                $query = $this->addSqlWithSorting($requestDatas, $query);
            }
            $query = $query->groupBy('task_assignments.id', 'users.id','task_timings.weight');
            $total = $query->get()->count();

            // Assuming $requestDatas contains the column and order parameters.
            $column = isset($requestDatas['column']) ? $requestDatas['column'] : null;
            $order = isset($requestDatas['order']) ? $requestDatas['order'] : null;
            if ($column && $order) {
                // Use dynamic ordering based on the provided column and order parameters.
                $query = $query->orderBy($column, $requestDatas['order']);
            } else {
                // Keep the original orderByRaw logic.
                $query = $query->orderByRaw('CASE task_assignments.status
                    WHEN 0 THEN 0
                    WHEN 1 THEN 1
                    WHEN 4 THEN 2
                    WHEN 6 THEN 3
                    WHEN 3 THEN 4
                    WHEN 2 THEN 5
                    WHEN 5 THEN 6
                END ASC, task_assignments.start_date DESC');
            }
            if ($total/$requestDatas['per_page'] < $requestDatas['current_page']) {
                $requestDatas['current_page'] = ceil($total/$requestDatas['per_page']);
            }

            $taskAssignments = $query->offset(($requestDatas['current_page'] - 1) * $requestDatas['per_page'])
                                ->limit($requestDatas['per_page'])
                                ->get();

            //no search results
            if (count($taskAssignments) === 0) {
                return [];
            }

            $data = [
                'items' => $taskAssignments,
                'currentPage' => $requestDatas['current_page'],
                'perPage' => $requestDatas['per_page'],
                'totalItems' => $total
            ];

            return $data;
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getDSelfCreatedTotal(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $data = $this->getTotalsIssuesType($requestDatas, 'd-self-created');

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getDAssignedTotal(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $data = $this->getTotalsIssuesType($requestDatas, 'd-assigned');

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getPSelfCreatedTotal(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $data = $this->getTotalsIssuesType($requestDatas, 'p-self-created');

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getPAssignedTotal(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $data = $this->getTotalsIssuesType($requestDatas, 'p-assigned');

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    private function getTotalsIssuesType($requestDatas, $type)
    {
        try {
            $user = Auth()->user();
            $types = config('const.task_assignment_type');
            $typeConditions = [
                'd-self-created' => 'users.department_id',
                'd-assigned' => 'task_assignments.assigned_department_id',
                'p-self-created' => 'task_assignments.tester_id',
                'p-assigned' => 'task_assignments.assigned_user_id',
            ];

            $query = TaskAssignment::selectRaw(DB::raw("
                task_assignments.type as type,
                count(distinct task_assignments.id) as total,
                count(distinct task_assignments.id) filter (where task_assignments.status = 0) as new,
                count(distinct task_assignments.id) filter (where task_assignments.status = 1) as open,
                count(distinct task_assignments.id) filter (where task_assignments.status = 2) as fixed,
                count(distinct task_assignments.id) filter (where task_assignments.status = 3) as cnr,
                count(distinct task_assignments.id) filter (where task_assignments.status = 4) as tfu,
                count(distinct task_assignments.id) filter (where task_assignments.status = 5) as confirmed,
                count(distinct task_assignments.id) filter (where task_assignments.status = 6) as nab")
            )
            ->when(($type === 'd-self-created'), function ($query) {
                $query->leftJoin('users', function ($join) {
                    $join->on('users.id', '=', 'task_assignments.tester_id');
                });
            })
            ->when(isset($requestDatas['weighted']), function ($query) {
                $query->join('task_timings', function ($join) {
                    $join->on('task_timings.task_assignment_id', '=', 'task_assignments.id')->whereNull('task_timings.deleted_at');
                });
            });
            if ($type === 'd-assigned') {
                $query->where($typeConditions[$type], $user->department_id);
            } elseif (in_array($type, ['p-self-created', 'p-assigned'])) {
                $query->where($typeConditions[$type], $user->id);
            }
            // if ($type === 'd-self-created') {
            //     if (!in_array($user->id, [51,107]) && $user->department_id != 5) {
            //         $query->where($typeConditions[$type], $user->department_id);
            //     }
            // } elseif ($type === 'd-assigned') {
            //     $query->where($typeConditions[$type], $user->department_id);
            // } else {
            //     $query->where($typeConditions[$type], $user->id);
            // }
            
            //Add SQL according to requested search conditions
            if (!empty($requestDatas)) {
                //get json from request
                $query = $this->addSqlWithSorting($requestDatas, $query);
            }
            $totals = $query->groupBy('task_assignments.type')->get();

            $typesLookup = collect(config('const.task_assignment_type'))->keyBy('value');
            $result = collect($totals)->map(function ($total) use ($typesLookup) {
                return [
                    'LABEL' => $typesLookup[$total->type]['label'] ?? 'Unknown',
                    'TOTAL' => $total->total,
                    'NEW' => $total->new,
                    'OPEN' => $total->open,
                    'FIXED' => $total->fixed,
                    'CNR' => $total->cnr,
                    'TFU' => $total->tfu,
                    'CONFIRMED' => $total->confirmed,
                    'NAB' => $total->nab,
                ];
            })->all();
            
            return $result;
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getDSelfCreatedReport(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $info = $this->getCountTaskAssignmentEachEmployee($requestDatas, 'dsc');
            
            return response()->json($info);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getDAssignedReport(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $info = $this->getCountTaskAssignmentEachEmployee($requestDatas, 'da');
            
            return response()->json($info);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    private function getCountTaskAssignmentEachEmployee($requestDatas, $type)
    {
        try {

            $query = TaskAssignment::leftJoin('users', 'users.id', '=', 'task_assignments.assigned_user_id')
            ->selectRaw(DB::raw("
                users.fullname,
                users.department_id,
                count(task_assignments.id) filter (where task_assignments.status = 0) as new,
                count(task_assignments.id) filter (where task_assignments.status = 1) as open,
                count(task_assignments.id) filter (where task_assignments.status = 2) as fixed,
                count(task_assignments.id) filter (where task_assignments.status = 3) as cnr,
                count(task_assignments.id) filter (where task_assignments.status = 4) as tfu,
                count(task_assignments.id) filter (where task_assignments.status = 5) as confirmed,
                count(task_assignments.id) filter (where task_assignments.status = 6) as nab"));
            
            //Add SQL according to requested search conditions
            if (!empty($requestDatas)) {
                //get json from request
                $query = $this->addSqlWithSorting($requestDatas, $query);
            }
            if ($type === 'da') {
                $query->where('task_assignments.assigned_department_id', Auth()->user()->department_id);
            }
            $info = $query->groupBy('users.id')->get();
            
            return $info;
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getTasks(Request $request)
    {
        try {
            $requestDatas = $request->all();
            
            //tasks
            $tasks = Task::join('task_projects', function ($join) use ($requestDatas) {
                $join->on('task_projects.task_id', '=', 'tasks.id')->whereNull('task_projects.deleted_at');
                // if (isset($requestDatas['project_id']) && !empty($requestDatas['project_id'])) {
                //     $join->where('task_projects.project_id', $requestDatas['project_id']);
                // }
                if (isset($requestDatas['project_id']) && !empty($requestDatas['project_id'])) {
                    $join->whereIn('task_projects.project_id', $requestDatas['project_id']);
                }
            })
            ->select('tasks.id as value', 'tasks.name as label')
            ->where(function ($query) use ($requestDatas) {
                if (isset($requestDatas['assigned_department_id']) &&
                    !empty($requestDatas['assigned_department_id'])) {
                    $query->where('department_id', $requestDatas['assigned_department_id']);
                }
            })
            ->when(
                isset($requestDatas['task_id']) && !empty($requestDatas['task_id']),
                function ($query) use ($requestDatas) {
                    $query->where('tasks.id', $requestDatas['task_id']);
                }
            )
            ->groupBy('tasks.id')
            ->get();

            return response()->json($tasks);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    /** Task Assignment Store
     *
     * @group Task Assignment
     *
     * @bodyParam description string nullable Mô tả
     * @bodyParam task_id biginteger nullable Mã công việc
     * @bodyParam assigned_department_id integer nullable Bộ phận
     * @bodyParam assigned_user_id biginteger nullable Người fix
     * @bodyParam start_date datetime required Ngày bắt đầu
     * @bodyParam status integer required Trạng thái
     * @bodyParam tag_test integer nullable Kết quả test
     * @bodyParam note text nullable Ghi chú
     *
     * @response 404 {
     *    'status' : 404,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 422 {
     *      "status": 422,
     *      "errors": "Tester không tồn tại",
     *      "errors_list": {
     *          "tester_id": [
     *              "Tester không tồn tại"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function store(TaskAssignmentRegisterRequest $request)
    {
        $requestDatas = $request->all();

        try {
            //init saveData
            $saveData = [
                'task_id' => isset($requestDatas['task_id']) ? $requestDatas['task_id'] : null,
                'project_id' => isset($requestDatas['project_id']) ? $requestDatas['project_id'] : null,
                'description' => isset($requestDatas['description']) ? $requestDatas['description'] : null,
                'tester_id' => Auth()->user()->id,

                'assigned_department_id' => isset($requestDatas['assigned_department_id']) ?
                    $requestDatas['assigned_department_id'] : null,

                'assigned_user_id' => isset($requestDatas['assigned_user_id']) ?
                    $requestDatas['assigned_user_id'] : null,

                'start_date' => Carbon::now(),
                'status' => isset($requestDatas['status']) ? $requestDatas['status'] : 0,
                'tag_test' => isset($requestDatas['tag_test']) ? $requestDatas['tag_test'] : null,
                'type' => isset($requestDatas['type']) ? $requestDatas['type'] : 0,
                'note' => isset($requestDatas['note']) ? $requestDatas['note'] : null,
            ];

            TaskAssignment::performTransaction(function ($model) use ($saveData) {
                TaskAssignment::create($saveData);
            });

            return response()->json([
                'success' => __('MSG-S-003'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function cloneById(Request $request)
    {
        try {
            $requestDatas = $request->all();

            if ($requestDatas['field'] == 'task_assignment_id') {
                $record = TaskTiming::where('task_assignment_id', $requestDatas['id'])->orderBy('work_date', 'desc')->first();

                $clonedRecord = $record->replicate();
                $clonedRecord->save();
            }

            return response()->json([
                'success' => __('MSG-S-003'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(TaskAssignmentEditRequest $request)
    {
        try {
            $editors = config('const.task_assignments_edit_role');
            //on request
            $requestDatas = $request->all();

            $model = new TaskAssignment();
            $connectionName = $model->getConnectionName();
            //start transaction control
            DB::connection($connectionName)->beginTransaction();

            $record = TaskAssignment::findOrFail($requestDatas['id']);

            if (array_key_exists('start_date', $requestDatas)) {
                $record->start_date = isset($requestDatas['start_date']) ? $requestDatas['start_date'] : null;
            }

            if (array_key_exists('tester_id', $requestDatas)) {
                $record->tester_id = isset($requestDatas['tester_id'])
                    ? $requestDatas['tester_id'] : null;
            }

            $prevTaskId = null;
            if (array_key_exists('task_id', $requestDatas)) {
                $prevTaskId = $record->task_id;
                $record->task_id = isset($requestDatas['task_id'])
                    ? $requestDatas['task_id'] : null;

                if ($requestDatas['task_id'] && $record->isDirty('task_id')) {
                    $this->assignTaskTiming($record, $requestDatas);
                }
            }

            if (array_key_exists('project_id', $requestDatas)) {
                $record->project_id = isset($requestDatas['project_id'])
                    ? $requestDatas['project_id'] : null;
            }

            if (array_key_exists('description', $requestDatas)) {
                $record->description = isset($requestDatas['description']) ? $requestDatas['description'] : null;
            }

            if (array_key_exists('assigned_department_id', $requestDatas)) {
                $record->assigned_department_id = isset($requestDatas['assigned_department_id'])
                    ? $requestDatas['assigned_department_id'] : null;
            }
            $prevAssignedUserId = null;
            if (array_key_exists('assigned_user_id', $requestDatas)) {
                $prevAssignedUserId = $record->assigned_user_id;
                $record->assigned_user_id = isset($requestDatas['assigned_user_id'])
                    ? $requestDatas['assigned_user_id'] : null;
            }

            if (array_key_exists('status', $requestDatas)) {
                $record->status = isset($requestDatas['status']) ? $requestDatas['status'] : 0;
            }

            if (array_key_exists('tag_test', $requestDatas)) {
                $record->tag_test = isset($requestDatas['tag_test']) ? $requestDatas['tag_test'] : null;
            }

            if (array_key_exists('note', $requestDatas)) {
                $record->note = isset($requestDatas['note']) ? $requestDatas['note'] : null;
            }

            if (array_key_exists('level', $requestDatas)) {
                $record->level = isset($requestDatas['level']) ? $requestDatas['level'] : null;
            }

            if (array_key_exists('type', $requestDatas)) {
                if (in_array(Auth()->user()->id, $editors) || Auth()->user()->id == $record->tester_id) {
                    $record->type = isset($requestDatas['type']) ? $requestDatas['type'] : null;

                    $this->updateTaskTimingType($record, $requestDatas);
                }
            }

            //insert task
            if ($record->save()) {
                if (array_key_exists('task_id', $requestDatas) && $prevTaskId != $requestDatas['task_id']) {
                    //store task status log
                    //issues with status is NEW, OPEN then assign task status is 9 "Đang fix bug"
                    if (in_array($record->status, [0,1])) {
                        $this->insertTaskStatusLog($prevTaskId, $requestDatas['task_id']);
                    }

                    if (!$record->task_id && $prevTaskId) {
                        TaskTiming::where('task_id', $prevTaskId)->where('task_assignment_id', $record->id)->delete();
                    }
                }

                if (array_key_exists('status', $requestDatas) && !in_array($record->status, [0,1])) {
                    //issues with status is not NEW, OPEN then revert task status before
                    $this->insertTaskStatusLog($record->task_id, null);
                } elseif (array_key_exists('status', $requestDatas) && in_array($record->status, [0,1])) {
                    //issues with status is NEW, OPEN then assign task status is 9 "Đang fix bug"
                    $this->insertTaskStatusLog(null, $record->task_id);
                }

                if (array_key_exists('assigned_user_id', $requestDatas) && $prevAssignedUserId != $requestDatas['assigned_user_id'] && $prevAssignedUserId != null) {
                    $taskTiming = TaskTiming::where('task_assignment_id', $record->id)->first();
                    if ($taskTiming) {
                        $taskTiming->estimate_time = null;
                        $taskTiming->time_spent = null;
                        $taskTiming->save();
                    }
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

    private function insertTaskStatusLog($prevTaskId, $currentTaskId)
    {
        if ($prevTaskId) {
            $prevTaskExists = TaskAssignment::where('task_id', $prevTaskId)
                ->whereIn('status', [0, 1])
                ->exists();
            
            if (!$prevTaskExists) {
                $log = TaskStatusLog::where('task_id', $prevTaskId)->first();

                if ($log) {
                    Task::where('id', $prevTaskId)->update(['status' => $log->old_status]);
                }
            }
        }

        if ($currentTaskId) {
            $task = Task::findOrFail($currentTaskId);

            TaskStatusLog::where('task_id', $task->id)->delete();
            TaskStatusLog::create([
                'task_id' => $task->id,
                'old_status' => $task->status,
                'new_status' => 9
            ]);

            //update task status to 9
            $task->status = 9;
            $task->save();
        }
    }

    private function assignTaskTiming($taskAssignment, $requestDatas)
    {
        $taskTimings = TaskTiming::where('task_assignment_id', $taskAssignment->id)->first();

        if ($taskTimings) {
            //avoid duplicate task_assignment_id in task_timings table, we have to delete it before insert
            if ($taskTimings->task_id != $requestDatas['task_id']) {
                $taskTimings->task_id = $requestDatas['task_id'];

                $taskTimings->save();
            }
        } else {
            //insert task timings
            TaskTiming::create([
                'task_id' => $requestDatas['task_id'],
                'task_assignment_id' => $taskAssignment->id,
                'type' => ($taskAssignment->type)+1,
                'description' => $taskAssignment->description,
                'work_date' => Carbon::now()->format('Y/m/d')
            ]);
        }
    }

    private function updateTaskTimingType($taskAssignment, $requestDatas)
    {
        $taskTiming = TaskTiming::where('task_assignment_id', $taskAssignment->id)->first();

        if ($taskTiming) {
            //update the type
            $taskTiming->type = $requestDatas['type'] >= 0 ? $requestDatas['type'] + 1 : null;

            $taskTiming->save();
        }
    }

    public function getTaskAssignmentById(Request $request)
    {
        try {
            $taskAssignment = TaskAssignment::select('id', 'note')->where('id', $request->id)->first();

            return response()->json($taskAssignment);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
        }
    }

    /** Delete Task Assignment
     *
     * @group Task Assignment
     *
     * @bodyParam id bigint required ID Task Assignment
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
    public function destroy(TaskAssignmentDeleteRequest $request)
    {
        try {
            $requestDatas = $request->all();
            $taskAssignment = TaskAssignment::findOrFail($requestDatas['id']);
            //exclusion control
            // $taskAssignment->setCheckUpdatedAt($requestDatas['check_updated_at']);

            TaskAssignment::performTransaction(function ($model) use ($taskAssignment, $requestDatas) {
                //delete record
                if ($taskAssignment->delete()) {
                    //deleted task timings
                    TaskTiming::where('task_assignment_id', $requestDatas['id'])->delete();
                }
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
        $query = CommonController::applyTaskFilters($query, $requestDatas);

        return $query;
    }
}
