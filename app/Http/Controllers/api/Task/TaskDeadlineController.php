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
use App\Models\Task;
use App\Models\TaskDeadline;
use App\Http\Requests\api\Task\Deadline\TaskDeadlineQuickEditRequest;
use Carbon\Carbon;

use App\Models\DeadlineModification;

/**
 * Task Deadline API
 *
 * @group Task Deadline
 */
class TaskDeadlineController extends Controller
{
    public function getEmployeeInfo()
    {
        try {
            $data = [
                'id' => Auth()->user()->id,
                'position' => Auth()->user()->position
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

    public function getList(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $deadlines = TaskDeadline::leftJoin('deadline_modifications', function ($join) {
                $join->on('deadline_modifications.task_deadline_id', '=', 'task_deadlines.id')
                    ->whereRaw('deadline_modifications.created_at = (SELECT MAX(created_at) FROM deadline_modifications WHERE task_deadline_id = task_deadlines.id)');
            })
            ->select([
                'task_deadlines.id',
                'task_deadlines.estimate_date',
                'task_deadlines.actual_date',
                'task_deadlines.status as task_status',
                \DB::raw('CASE WHEN deadline_modifications.deleted_at IS NOT NULL THEN 4 ELSE deadline_modifications.status END AS request_status'),
                'deadline_modifications.requested_deadline',
                'deadline_modifications.reason',
                'deadline_modifications.feedback',
                'deadline_modifications.id as deadline_modification_id',
            ])
            ->where('task_deadlines.task_id', $requestDatas['id'])
            ->orderBy('task_deadlines.created_at', 'asc')->get();
            
            // get deadline status = 0
            $deadlineApproved = DeadlineModification::select([
                'deadline_modifications.id as deadline_modification_id',
                \DB::raw('CASE WHEN deadline_modifications.deleted_at IS NOT NULL THEN 4 ELSE deadline_modifications.status END AS request_status'),
                'deadline_modifications.requested_deadline',
                'deadline_modifications.requested_deadline as estimate_date',
                'deadline_modifications.reason',
                'deadline_modifications.feedback',
            ])
            ->where('deadline_modifications.task_id', $requestDatas['id'])
            ->where('deadline_modifications.status', 0)
            ->orderBy('deadline_modifications.created_at', 'asc')->get();

            $deadlines = $deadlines->concat($deadlineApproved)->map(function ($deadline) {
                $requestStatus = $deadline->request_status ?? -1;

                switch ($requestStatus) {
                    case 0:
                        $deadline->request_status_text = 'Đang chờ';
                        break;
                    case 1:
                        $deadline->request_status_text = 'Chậm Deadline';
                        break;
                    case 2:
                        $deadline->request_status_text = 'Từ Chối';
                        break;
                    case 3:
                        $deadline->request_status_text = 'Duyệt';
                        break;
                    case 4:
                        $deadline->request_status_text = 'Xóa';
                        break;
                    default:
                        $deadline->request_status_text = '';
                }
            
                return $deadline;
            });
            return response()->json($deadlines);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function store(Request $request) {
        try {
            $requestDatas = $request->all();
            $user = Auth()->user();

            $model = new TaskDeadline();
            $connectionName = $model->getConnectionName();
            //start transaction control
            DB::connection($connectionName)->beginTransaction();

            $count = 0;
            if ($user->position < 1 && $user->id != 107) {
                $count = TaskDeadline::where('user_id', $user->id)
                    ->where('task_id', $requestDatas['id'])
                    ->count();
            }
            if ($count > 0) {
                return response()->json([
                    'status' => Response::HTTP_OK,
                    'errors' => __('MSG-E-027'),
                ], Response::HTTP_OK);
            }

            $deadline = TaskDeadline::create([
                'user_id' => $user->id,
                'task_id' => $requestDatas['id'],
                'status' => 1
            ]);

            //update deadline to Task
            CommonController::updateDeadlineToTask($deadline);

            DB::connection($connectionName)->commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::connection($connectionName)->rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function quickUpdate(TaskDeadlineQuickEditRequest $request)
    {
        try {
            $requestDatas = $request->all();
            $isUpdateDate = false;

            $model = new TaskDeadline();
            $connectionName = $model->getConnectionName();
            //start transaction control
            DB::connection($connectionName)->beginTransaction();

            $deadline = TaskDeadline::findOrFail($requestDatas['id']);
            $originalEstimateDate = $deadline->estimate_date;
            $originalActualDate = $deadline->actual_date;
        
            if (array_key_exists('estimate_date', $requestDatas)) {
                $isUpdateDate = true;
                if (!$originalEstimateDate || Auth()->user()->position > 0) {
                    $deadline->estimate_date = $requestDatas['estimate_date'];
                }
            }

            if (array_key_exists('actual_date', $requestDatas)) {
                $isUpdateDate = true;
                if (!$originalActualDate || Auth()->user()->position > 0) {
                    $deadline->actual_date = $requestDatas['actual_date'];
                }
            }

            if (array_key_exists('status', $requestDatas)) {
                $deadline->status = $requestDatas['status'];
            }

            //insert task
            $deadline->save();

            if ($isUpdateDate) {
                //update deadline to Task
                CommonController::updateDeadlineToTask($deadline);
            }

            DB::connection($connectionName)->commit();

            if (array_key_exists('estimate_date', $requestDatas)) {
                if ($originalEstimateDate && Auth()->user()->position == 0) {
                    return response()->json([
                        'warning' => __('MSG-I-009'),
                        'deadline' => $requestDatas['estimate_date']
                    ], Response::HTTP_OK);
                }
            }
            if (array_key_exists('actual_date', $requestDatas)) {
                if ($originalActualDate && Auth()->user()->position == 0) {
                    return response()->json([
                        'warning' => __('MSG-I-009'),
                        'deadline' => $requestDatas['actual_date']
                    ], Response::HTTP_OK);
                }
            }

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

    public function destroy(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $model = new TaskDeadline();
            $connectionName = $model->getConnectionName();
            //start transaction control
            DB::connection($connectionName)->beginTransaction();

            $item = TaskDeadline::findOrFail($requestDatas['id']);
            $taskId = $item->task_id;
            //delete task deadline
            $item->delete();

            // Detect if there are no deadlines left for this task
            $remainingDeadlines = TaskDeadline::where('task_id', $taskId)->exists();
            // Update task's deadline if there are no more deadlines
            if (!$remainingDeadlines) {
                $task = Task::findOrFail($taskId);
                $task->deadline = null;
                $task->save();
            }

            DB::connection($connectionName)->commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::connection($connectionName)->rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
