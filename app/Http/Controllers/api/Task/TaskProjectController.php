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
use App\Models\TaskProject;
use App\Models\Task;
use App\Models\Project;
use App\Models\Sticker;
use App\Models\Priority;
use App\Http\Requests\api\Task\TaskProject\TaskProjectQuickEditRequest;
use App\Http\Requests\api\Task\TaskProject\TaskProjectRegisterRequest;
use App\Http\Requests\api\Task\TaskProject\TaskProjectDeleteRequest;
use Carbon\Carbon;

/**
 * Task Project API
 *
 * @group Task Project
 */
class TaskProjectController extends Controller
{
    public function getSelbox(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $projects = Project::select('id', 'name')->orderBy('ordinal_number', 'asc')->get();

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

            $data = [
                'projects' => $projects,
                'stickers' => $stickers,
                'priorities' => $priorities
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

    public function list(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $task = Task::select('id', 'name', 'sticker_id', 'priority', 'weight')
                        ->where('id', $requestDatas['task_id'])
                        ->first();

            $taskProject = TaskProject::select('id', 'project_id', 'percent', 'weight')
                        ->where('task_id', $requestDatas['task_id'])
                        ->orderBy('id', 'desc')
                        ->get();

            $data = [
                'task' => $task,
                'task_project' => $taskProject
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

    public function store(TaskProjectRegisterRequest $request)
    {
        try {
            $requestDatas = $request->all();

            TaskProject::performTransaction(function ($model) use ($requestDatas) {
                TaskProject::create([
                    'task_id' => $requestDatas['task_id'],
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

    public function quickUpdate(TaskProjectQuickEditRequest $request)
    {
        try {
            // on request
            $requestDatas = $request->all();

            $row = TaskProject::join('tasks', function ($join) {
                $join->on('tasks.id', '=', 'task_projects.task_id')->whereNull('tasks.deleted_at');
            })
            ->select(
                'tasks.weight as total_weight',
                'task_projects.id as id',
                'task_projects.project_id as project_id',
                'task_projects.weight as weight',
                'task_projects.task_id as task_id',
                'task_projects.percent as percent',
            )
            ->where('task_projects.id', $requestDatas['id'])
            ->first();

            $taskProjects = TaskProject::where('task_id', $row->task_id)
                            ->where('id', '!=', $row->id)
                            ->get();

            $model = new TaskProject();
            $connectionName = $model->getConnectionName();
            //start transaction control
            DB::connection($connectionName)->beginTransaction();

            if (isset($requestDatas['project_id']) && !empty($requestDatas['project_id'])) {
                //check duplicate project id in same task_id
                $isDuplicate = $this->checkDuplicateProjectId($requestDatas, $row);
                if ($isDuplicate) {
                    return response()->json(
                        [
                            'status' => Response::HTTP_NOT_FOUND,
                            'errors' => __('MSG-E-025')
                        ],
                        Response::HTTP_NOT_FOUND
                    );
                }

                $row->project_id = $requestDatas['project_id'];
            }

            if (array_key_exists('percent', $requestDatas)) {
                //check percent and weight
                $message = $this->checkPercentWeight($requestDatas, $row, $taskProjects);
                if ($message) {
                    return response()->json(
                        ['status' => Response::HTTP_NOT_FOUND,
                        'errors' => $message
                        ],
                        Response::HTTP_NOT_FOUND
                    );
                }

                $row->percent = $requestDatas['percent'];
            }

            if (array_key_exists('weight', $requestDatas)) {
                //check percent and weight
                $message = $this->checkPercentWeight($requestDatas, $row, $taskProjects);
                if ($message) {
                    return response()->json(
                        ['status' => Response::HTTP_NOT_FOUND,
                        'errors' => $message
                        ],
                        Response::HTTP_NOT_FOUND
                    );
                }

                $row->weight = $requestDatas['weight'];
            }

            //insert task
            $row->save();

            DB::connection($connectionName)->commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::connection($connectionName)->rollBack();
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(TaskProjectDeleteRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $row = TaskProject::join('tasks', function ($join) {
                $join->on('tasks.id', '=', 'task_projects.task_id')->whereNull('tasks.deleted_at');
            })
            ->select(
                'tasks.weight as total_weight',
                'task_projects.id as id',
                'task_projects.project_id as project_id',
                'task_projects.weight as weight',
                'task_projects.task_id as task_id',
                'task_projects.percent as percent',
            )
            ->where('task_projects.id', $requestDatas['id'])
            ->first();

            $taskProjects = TaskProject::where('task_id', $row->task_id)
                            ->where('id', '!=', $row->id)
                            ->get();

            TaskProject::performTransaction(function ($model) use ($row, $taskProjects) {
                $this->splitPercentWeight($row, $taskProjects);
                //delete project
                $row->delete();
            });

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function checkDuplicateProjectId($requestDatas, $row)
    {
        $isDuplicate = false;
        $record = TaskProject::where('project_id', $requestDatas['project_id'])
                        ->where('task_id', $row->task_id)
                        ->first();

        if ($record) {
            $isDuplicate = true;
        }

        return $isDuplicate;
    }

    private function checkPercentWeight($requestDatas, $row, $taskProjects)
    {
        $message = "";
        
        if (isset($requestDatas['weight']) && !empty($requestDatas['weight'])) {
            $totalWeight = $taskProjects->sum('weight') + $requestDatas['weight'];
            if ($totalWeight > $row->total_weight) {
                $message = __('MSG-E-023', ['attribute' => $row->total_weight]);
            }
        }

        if (isset($requestDatas['percent']) && !empty($requestDatas['percent'])) {
            $totalPercent = $taskProjects->sum('percent') + $requestDatas['percent'];
            if ($totalPercent > 100) {
                $message = __('MSG-E-024');
            }
        }

        //check the last row changed, if total percent is not equal 100, return error
        $idsWithNullPercent = $taskProjects->filter(function ($taskProject) {
            return is_null($taskProject->percent) || $taskProject->percent === '';
        })->pluck('id')->toArray();

        $totalPercent = $taskProjects->sum('percent') + $requestDatas['percent'];
        if (count($idsWithNullPercent) == 0 && $totalPercent < 100) {
            $message = __('MSG-E-026');
        }


        return $message;
    }

    private function splitPercentWeight($row, $taskProjects)
    {
        $totalWeightAdded = $row->total_weight - $taskProjects->sum('weight');
        if ($row->weight > 0) {
            $totalWeightAdded = $row->weight;
        }

        foreach ($taskProjects as $item) {
            if ($totalWeightAdded > 0) {
                //split lost weight to all by total rows
                $addWeight = $totalWeightAdded / count($taskProjects);
                //calculate the percent base on the add weight
                $addPercent = $addWeight * 100 / $row->total_weight;

                //insert the add weight and add percent
                $item->weight += $addWeight;
                $item->percent += $addPercent;

                $item->save();
            }
        }
    }
}
