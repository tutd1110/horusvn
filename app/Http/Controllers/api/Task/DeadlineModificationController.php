<?php

namespace App\Http\Controllers\api\Task;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api\CommonController;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Models\DeadlineModification;
use App\Models\User;
use App\Models\Task;
use App\Models\TaskDeadline;
use App\Models\UserAlert;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\api\Task\DeadlineModification\GetListRequest;
use App\Http\Requests\api\Task\DeadlineModification\DeadlineModRegisterRequest;
use Carbon\Carbon;

/**
 * Deadline Modification API
 *
 * @group Holiday
 */
class DeadlineModificationController extends Controller
{
    public function getSelboxes()
    {
        try {
            $users = User::select('id', 'fullname', 'department_id')->orderBy('position', 'asc')->get();
            $departments = CommonController::getDepartmentsJob();
            //employees role
            $pmIdsRole = config('const.employee_id_pm_roles');
            //user login id
            $userId = Auth()->user()->id;

            $selboxes = [
                'users' => $users,
                'departments' => $departments,
                'user_info' => [
                    'user_id' => $userId,
                    'is_permission' => in_array($userId, $pmIdsRole) ? true : false
                ]
            ];

            return response()->json($selboxes);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getList(GetListRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();
            $user = Auth()->user();

            $query = DeadlineModification::join('users', function ($join) {
                $join->on('users.id', '=', 'deadline_modifications.user_id')
                    ->whereNull('users.deleted_at');
            })->join('tasks', function ($join) {
                $join->on('tasks.id', '=', 'deadline_modifications.task_id')
                    ->whereNull('tasks.deleted_at');
            })->select(
                'deadline_modifications.id',
                'users.fullname',
                'tasks.name as task_name',
                'tasks.deadline as old_deadline',
                'deadline_modifications.original_deadline',
                'deadline_modifications.requested_deadline',
                'deadline_modifications.reason',
                'deadline_modifications.status',
                'deadline_modifications.type',
                'deadline_modifications.created_at',
                'deadline_modifications.task_id'
            )->where('deadline_modifications.status', $requestDatas['status'])
            ->when($user->position == 1 && $user->id != 107 && $user->id != 161, function ($query) use ($user) {
                $query->where('users.department_id', $user->department_id);
            })->when($user->position == 0 && $user->id != 107 && $user->id != 161, function ($query) use ($user) {
                $query->where('users.id', $user->id);
            });

            //Add SQL according to requested search conditions
            if (!empty($requestDatas)) {
                //get json from request
                $query = $this->addSqlWithSorting($requestDatas, $query);
            }
            $query = $query->orderBy('deadline_modifications.requested_deadline', 'desc');

            $total = $query->get()->count();
            //no search results
            if ($total === 0) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }
            if ($total/$requestDatas['per_page'] < $requestDatas['current_page']) {
                $requestDatas['current_page'] = ceil($total/$requestDatas['per_page']);
            }

            $list = $query->offset(($requestDatas['current_page'] - 1) * $requestDatas['per_page'])
                                ->limit($requestDatas['per_page'])
                                ->get();

            $data = [
                'items' => $list,
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

    public function updatedStatus(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();
            //employees role
            $pmIdsRole = config('const.employee_id_pm_roles');
            if (!in_array(Auth()->user()->id, $pmIdsRole)) {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }
            $item = DeadlineModification::findOrFail($requestDatas['id']);

            if (!$item->status) {
                $item->status = $requestDatas['status'];
                if ( isset($requestDatas['feedback'])) {
                    $item->feedback = $requestDatas['feedback'];
                }

                //start transaction control
                DB::beginTransaction();

                $item->save();

                if ($requestDatas['status'] == 1 || $requestDatas['status'] == 3) {
                    $requestDeadline = Carbon::createFromFormat('d/m/Y', $item->requested_deadline)->format('Y/m/d');

                    if ($item->task_deadline_id) {
                        $deadline = TaskDeadline::findOrFail($item->task_deadline_id);

                        $deadline->estimate_date = $requestDeadline;
                        $deadline->save();
                    }

                    //employees request add new deadline
                    if ($item->type === 1) {
                        $taskDeadline = TaskDeadline::create([
                            'user_id' => $item->user_id,
                            'task_id' => $item->task_id,
                            'estimate_date' => $requestDeadline,
                            'status' => 1
                        ]);
                        if ($taskDeadline) {
                            $item->task_deadline_id = $taskDeadline['id'];
                            $item->save();
                        }
                    }

                    // $maxEstimateDate = TaskDeadline::where('task_id', $item->task_id)->max('estimate_date');
                    // $task = Task::findOrFail($item->task_id);

                    // $task->deadline = $maxEstimateDate;
                    // $task->save();
                    //update deadline to Task
                    CommonController::updateDeadlineToTask($item);
                }
                else if ($requestDatas['status'] == 2) {
                    $requestDeadline = Carbon::createFromFormat('d/m/Y', $item->requested_deadline)->format('Y/m/d');

                    // if ($item->task_deadline_id) {
                    //     $deadline = TaskDeadline::findOrFail($item->task_deadline_id);
                    //     $deadline->estimate_date = $requestDeadline;
                    //     $deadline->save();
                    // }

                    //employees request add new deadline
                    if ($item->type === 1) {
                        $taskDeadline = TaskDeadline::create([
                            'user_id' => $item->user_id,
                            'task_id' => $item->task_id,
                            'estimate_date' => $requestDeadline,
                            // 'status' => 1
                        ]);
                        if ($taskDeadline) {
                            $item->task_deadline_id = $taskDeadline['id'];
                            $item->save();
                        }
                    }

                }

                //init entry data to insert to user_alerts table
                $entry = [
                    'user_id' => Auth()->user()->id,
                    'action' => 'updated',
                    'resource_type' => 'DeadlineModification',
                    'resource_id' => $item->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];

                // Insert the data into the user_works table
                UserAlert::insert($entry);

                DB::commit();

                return response()->json([
                    'success' => __('MSG-S-001'),
                ], Response::HTTP_OK);
            }
            else if ($item->status) {
                $item->status = $requestDatas['status'];
                
                $formattedDeadline = Carbon::createFromFormat('d/m/Y', $item->requested_deadline)->format('Y-m-d');
                $deadline = TaskDeadline::where('task_id', $item->task_id)
                            ->where('estimate_date', $formattedDeadline)
                            ->first();
                if ($deadline && $item->task_deadline_id == null) {
                    $item->task_deadline_id = $deadline->id;
                }
                $item->save();
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(DeadlineModRegisterRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $task = Task::findOrFail($requestDatas['task_id']);

            //start transaction control
            DB::beginTransaction();
            //insert Deadline Modification
            $deadlinem = DeadlineModification::create([
                'user_id' => $task->user_id,
                'task_id' => $task->id,
                'task_deadline_id' => !isset($requestDatas['type']) ? $requestDatas['task_deadline_id'] : null,
                'original_deadline' => !isset($requestDatas['type']) ? $task->deadline : null,
                'requested_deadline' => $requestDatas['deadline'],
                //type is 1, employees request add new deadline, else modify the deadline
                'type' => isset($requestDatas['type']) ? $requestDatas['type'] : null,
                'reason' => $requestDatas['reason']
            ]);

            //init entry data to insert to user_alerts table
            $entry = [
                'user_id' => Auth()->user()->id,
                'action' => 'created',
                'resource_type' => 'DeadlineModification',
                'resource_id' => $deadlinem->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
            // Insert the data into the user_works table
            UserAlert::insert($entry);

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-006'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();
            //employees role
            $pmIdsRole = config('const.employee_id_pm_roles');

            $item = DeadlineModification::findOrFail($requestDatas['id']);
            if (!in_array(Auth()->user()->id, $pmIdsRole) && Auth()->user()->id != $item->user_id) {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            //init entry data to insert to user_alerts table
            $entry = [
                'user_id' => Auth()->user()->id,
                'action' => 'deleted',
                'resource_type' => 'DeadlineModification',
                'resource_id' => $item->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];

            //start transaction control
            DB::beginTransaction();

            // Insert the data into the user_works table
            UserAlert::insert($entry);

            //delete deadline modification
            $item->delete();

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
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

        //Change the SQL according to the requested search conditions
        if (isset($requestDatas['user_id'])) {
            $addedQuery = $addedQuery->where('deadline_modifications.user_id', $requestDatas['user_id']);
        }

        if (isset($requestDatas['department_id'])) {
            $addedQuery = $addedQuery->where('users.department_id', $requestDatas['department_id']);
        }

        return $addedQuery;
    }
}
