<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Client;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Holiday;
use App\Models\HolidayOffset;
use App\Models\User;
use App\Models\Mentee;
use App\Models\Task;
use App\Models\TaskDeadline;
use App\Models\TaskAssignment;
use App\Models\TaskProject;
use App\Models\TaskTiming;
use App\Models\TaskTimingProject;
use App\Models\Sticker;
use App\Models\Priority;
use App\Models\Review;
use App\Models\EmployeeReviewPoint;
use App\Models\QuestionReview;
use App\Models\EmployeeAnswer;
use App\Models\ContentReview;
use App\Models\ReviewComment;
use App\Models\Violation;
use App\Models\Department;
use App\Models\Company;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use File;

use App\Http\Controllers\api\Timesheet\TimesheetController;
use App\Http\Requests\api\Timesheet\GetReportRequest;

/**
 * Common API
 *
 * @group Common
 */
class CommonController extends Controller
{
    public function reloadFromTaskTiming(Request $request)
    {
        try {
            //employees role
            $leaderIdsRole = config('const.employee_id_leader_roles');

            //user id login
            $userId = Auth()->user()->id;

            //On request
            $requestDatas = $request->all();

            $workDatePeriod = $this->getWorkDatePeriod($requestDatas);

            $requestDatas['start_time'] = $workDatePeriod['start_time'];
            $requestDatas['end_time'] = $workDatePeriod['end_time'];

            $task = Task::join('task_timings', function ($join) use ($requestDatas, $userId, $leaderIdsRole) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');

                if (!empty($requestDatas['start_time'])) {
                    $join->whereDate('task_timings.work_date', '>=', $requestDatas['start_time']);
                }
                if (!empty($requestDatas['end_time'])) {
                    $join->whereDate('task_timings.work_date', '<=', $requestDatas['end_time']);
                }

                if (isset($requestDatas['task_user_id']) && $userId != $requestDatas['task_user_id']
                    && !in_array($userId, $leaderIdsRole)) {
                    if (isset($requestDatas['task_issue_ids']) && count($requestDatas['task_issue_ids']) > 0) {
                        $join->whereIn('task_timings.id', $requestDatas['task_issue_ids']);
                    }
                }
            })
            ->selectRaw(DB::raw("
                tasks.id as id,
                min(task_timings.work_date) as start_time,
                max(task_timings.work_date) as end_time,
                coalesce(sum(task_timings.estimate_time), 0) as total_estimate_time,
                coalesce(sum(task_timings.time_spent), 0) as total_time_spent"))
            ->where('tasks.id', $requestDatas['id'])
            ->groupBy('tasks.id')
            ->first();

            //no search results
            if (!$task) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $data = [
                'id' => $task->id,
                'start_time' => Carbon::parse($task->start_time)->format('d/m/Y'),
                'end_time' => Carbon::parse($task->end_time)->format('d/m/Y'),
                'total_estimate_time' => $task->total_estimate_time,
                'total_time_spent' => $task->total_time_spent
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

    public function reloadFromTaskProject(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $subquery = TaskProject::select('task_id', DB::raw('array_agg(project_id) as project_ids'))
                    ->whereNull('deleted_at')
                    ->groupBy('task_id');

            $query = Task::leftJoinSub($subquery, 'pj', function ($join) {
                $join->on('pj.task_id', '=', 'tasks.id');
            })
            ->selectRaw(DB::raw("
                tasks.id as id,
                tasks.sticker_id as sticker_id,
                tasks.priority as priority,
                tasks.weight as weight,
                coalesce(nullif(pj.project_ids, '{null}'), null) as project_id"));

            $task = $query->where('tasks.id', $requestDatas['id'])->groupBy('tasks.id', 'pj.project_ids')->first();

            //no search results
            if (!$task) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json($task);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getStickers(Request $request)
    {
        try {
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
                    $query->where('department_id', $request->department_id);
                }
            })
            ->get();

            return response()->json($stickers);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getReviewSelboxes(Request $request)
    {
        try {
            //review type
            $period = config('const.review_period');
            //employees
            $employees = User::select('id', 'fullname')->get();

            $data = [
                'employees' => $employees,
                'period' => $period
            ];

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public static function getDepartments()
    {
        try {
            //const departments
            // $departments = config('const.departments');

            // $departments = array_map(function ($id, $name) {
            //     return ['id' => $id, 'name' => $name];
            // }, array_keys($departments), $departments);

            $departments = Department::whereNull('departments.deleted_at')
            ->orderBy('departments.id', 'asc')
            ->get()->toArray();
            return response()->json($departments);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getEmployees(Request $request)
    {
        try {
            //employees
            $employees = User::select( 'id', 'fullname', 'department_id', 'position' )
                                ->when(!empty($request->user_status), function($query) use ($request){
                                    $query->where('user_status',$request->user_status);
                                })
                                ->get();

            return response()->json($employees);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public static function getEmployeesWorking()
    {
        try {
            //employees
            $employees = User::select('id', 'fullname', 'department_id','user_code')->where('user_status' , 1)->get();

            // return response()->json($employees);
            return $employees;
        } catch (Exception $e) {
            Log::error($e->getMessage());
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
                default:
                    # code...
                    break;
            }
        } else {
            if (!empty($requestDatas['start_time']) && !empty($requestDatas['end_time'])) {
                $duration['start_time'] = Carbon::create($requestDatas['start_time'])->format('Y/m/d');
                $duration['end_time'] = Carbon::create($requestDatas['end_time'])->format('Y/m/d');
            }
        }

        return $duration;
    }

    public static function syncTaskProjects(Task $task, array $projectIds)
    {
        $attributesChanged = false;

        $currentProjectIds = $task->taskProjects()->pluck('project_id')->toArray();

        $projectIdsToAdd = array_diff($projectIds, $currentProjectIds);
        $projectIdsToRemove = array_diff($currentProjectIds, $projectIds);

        // Add the new project_id associations
        if (count($projectIdsToAdd) > 0) {
            foreach ($projectIdsToAdd as $projectId) {
                $task->taskProjects()->create([
                    'task_id' => $task->id,
                    'project_id' => $projectId
                ]);
            }

            if (in_array(null, $currentProjectIds)) {
                $task->taskProjects()->whereNull('project_id')->delete();
            }

            $attributesChanged = true;
        }

        // Remove the old project_id associations
        if (count($projectIdsToRemove) > 0) {
            if (count($currentProjectIds) == 1 && count($projectIdsToRemove) == 1 && count($projectIdsToAdd) == 0) {
                $task->taskProjects()->where('project_id', $projectIdsToRemove[0])->update(['project_id' => null]);
            } else {
                $task->taskProjects()->whereIn('project_id', $projectIdsToRemove)->delete();
            }

            TaskTimingProject::where('task_id', $task->id)
                ->whereIn('project_id', $projectIdsToRemove)
                ->delete();

            $attributesChanged = true;
        }

        return $attributesChanged;
    }

    public static function applyTaskFilters($query, $requestDatas)
    {
        $addedQuery = $query;

        if (isset($requestDatas['id'])) {
            $addedQuery = $addedQuery->where('task_assignments.id', $requestDatas['id']);
        }

        if (isset($requestDatas['project_id'])) {
            if ($requestDatas['project_id'] !== 0) {
                $addedQuery = $addedQuery->where('task_assignments.project_id', $requestDatas['project_id']);
            } else {
                $addedQuery = $addedQuery->whereNull('task_assignments.project_id')->orWhere('task_assignments.project_id', 0);
            }
        }

        if (isset($requestDatas['task_id'])) {
            if ($requestDatas['task_id'] !== 0) {
                $addedQuery = $addedQuery->where('task_assignments.task_id', $requestDatas['task_id']);
            } else {
                $addedQuery = $addedQuery->whereNull('task_assignments.task_id');
            }
        }

        if (isset($requestDatas['assigned_department_id']) && count($requestDatas['assigned_department_id']) > 0) {
            $departmentIds = array_filter($requestDatas['assigned_department_id']);

            $addedQuery = $addedQuery->where(function ($groupQuery) use ($requestDatas, $departmentIds) {
                if (!empty($departmentIds)) {
                    $groupQuery->whereIn('task_assignments.assigned_department_id', $departmentIds);
                }

                if (in_array(0, $requestDatas['assigned_department_id'])) {
                    $groupQuery->orWhereNull('task_assignments.assigned_department_id');
                }
            });
        }

        if (isset($requestDatas['status']) && count($requestDatas['status']) > 0) {
            $addedQuery = $addedQuery->whereIn('task_assignments.status', $requestDatas['status']);
        }

        if (isset($requestDatas['tester_id'])) {
            if ($requestDatas['tester_id'] !== 0) {
                $addedQuery = $addedQuery->where('task_assignments.tester_id', $requestDatas['tester_id']);
            } else {
                $addedQuery = $addedQuery->whereNull('task_assignments.tester_id');
            }
        }

        if (isset($requestDatas['assigned_user_id'])) {
            if ($requestDatas['assigned_user_id'] !== 0) {
                $addedQuery = $addedQuery->where(
                    'task_assignments.assigned_user_id',
                    $requestDatas['assigned_user_id']
                );
            } else {
                $addedQuery = $addedQuery->whereNull('task_assignments.assigned_user_id');
            }
        }

        if (!empty($requestDatas['description'])) {
            $addedQuery->where(function($query) use ($requestDatas) {
                foreach ($requestDatas['description'] as $description) {
                    $description = (string) $description;
                    $query->orWhere(DB::raw('lower(task_assignments.description)'), 'LIKE', '%' . mb_strtolower(urldecode($description), 'UTF-8') . '%');
                }
            });
        }

        if (isset($requestDatas['weighted'])) {
            $addedQuery = $addedQuery->whereNull('task_timings.weight')->orWhere('task_timings.weight', 0);
        }

        if (isset($requestDatas['level'])) {
            $addedQuery = $addedQuery->where('task_assignments.level', $requestDatas['level']);
        }

        if (isset($requestDatas['start_time']) && isset($requestDatas['end_time'])) {
            $startTime = Carbon::parse($requestDatas['start_time'])->format('Y/m/d 00:00:00');
            $endTime = Carbon::parse($requestDatas['end_time'])->format('Y/m/d 23:59:59');

            $addedQuery = $addedQuery->where('task_assignments.start_date', '>=', $startTime)
                            ->where('task_assignments.start_date', '<=', $endTime);
        }

        return $addedQuery;
    }

    public static function loadReviewData($requestDatas)
    {
        try {
            $status = config('const.review_statuses');

            //get review
            $review = Review::select('id', 'period', 'employee_id', 'start_date')
                ->where('id', $requestDatas['id'])
                ->first();

            $beforeReview = Review::select('id', 'period', 'employee_id', 'start_date')
                ->where('employee_id', $review->employee_id)
                ->whereDate('start_date', '<', $review->start_date)
                ->orderBy('start_date', 'desc')
                ->first();

            //get content review
            $content = EmployeeReviewPoint::join('content_reviews', function ($join) {
                $join->on('content_reviews.id', '=', 'employee_review_points.content_review_id')
                    ->whereNull('content_reviews.deleted_at');
            })
            ->selectRaw(DB::raw("
                employee_review_points.id as id,
                content_reviews.content as content,
                employee_review_points.employee_point as employee_point,
                employee_review_points.mentor_point as mentor_point,
                employee_review_points.leader_point as leader_point,
                employee_review_points.pm_point as pm_point"))
            ->where('employee_review_points.review_id', $review->id)
            ->orderBy('content_reviews.id', 'asc')
            ->get();
            //end

            //get review questions
            $questions = EmployeeAnswer::join('question_reviews', function ($join) {
                $join->on('question_reviews.id', '=', 'employee_answers.question_review_id')
                    ->whereNull('question_reviews.deleted_at');
            })
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'employee_answers.employee_id')
                    ->whereNull('users.deleted_at');
            })
            ->with(['reviewFiles' => function ($query) {
                $query->select('id', 'file_path', 'employee_answer_id');
            }])
            ->select(
                'employee_answers.id as id',
                'employee_answers.employee_id as employee_id',
                'question_reviews.question as question',
                'question_reviews.id as question_id',
                'users.fullname as fullname',
                'employee_answers.employee_answer as employee_answer',
                'employee_answers.type as type'
            )
            ->where('employee_answers.review_id', $review->id)
            ->orderByRaw('employee_answers.type asc, question_reviews.id asc')
            ->get();
            $questions->each(function ($question) {
                $question->type = $question->type >= 1 ? intval($question->type) : round($question->type, 1);
            });
            //end

            //get group employee for the review
            $groups = $questions->groupBy('fullname');
            $employees = $groups->map(function ($question, $departmentId) {
                return [
                    'id' => $question->first()->employee_id,
                    'fullname' => $question->first()->fullname,
                    'position' => $question->first()->type,
                ];
            })->values()->all();
            usort($employees, function ($a, $b) {
                return $a['position'] <=> $b['position'];
            });

            //pm/director comments
            $comment = ReviewComment::where('review_id', $review->id)->first();
            $comment->status_text = isset($status[$comment->status]) ? $status[$comment->status] : null;
            //end
            $employee = User::select('id', 'department_id', 'fullname', 'position', 'created_at')->where('id', $review->employee_id)
                        ->first();
            $violations = Violation::select(
                'violations.type', 
                'violations.description',
                'violations.user_id',
                'violations.time',
            )
            ->whereBetween('time', [
                $beforeReview != null ? Carbon::parse($beforeReview->start_date)->format('Y-m-d h:m:s') : Carbon::parse($employee->created_at),
                Carbon::parse($review->start_date)->format('Y-m-d h:m:s')
            ])
            ->where('user_id', $employee->id)
            ->orderBy('violations.time', 'asc')->get();

            $violations = $violations->map(function ($violation) {
                $violation->time_violation = Carbon::parse($violation->time)->format('d/m/Y');
                return $violation;
            });

            //get table user timesheet
            $request = [
                'user_id' =>  $employee->id,
                'start_date' =>  $beforeReview != null ? Carbon::parse($beforeReview->start_date)->format('Y/m/d') : Carbon::parse($employee->created_at)->format('Y/m/d'),
                'end_date' =>  Carbon::parse($review->start_date)->format('Y/m/d')
            ];
            $timesheetController = new TimesheetController();
            $requestObject = new GetReportRequest($request);
            $userTable = $timesheetController->getReport($requestObject);
            
            $review->start_date = Carbon::parse($review->start_date)->format('d/m/Y');
            $data = [
                'review' => $review,
                'employee' => $employee,
                'employees' => $employees,
                'content' => $content,
                'questions' => $questions,
                'comment' => $comment,
                'violations' => $violations,
                'before_review' => $beforeReview != null ? Carbon::parse($beforeReview->start_date)->format('d/m/Y') : Carbon::parse($employee->created_at)->format('d/m/Y'),
                'user_table' => $userTable->getData()
            ];

            return $data;
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public static function isDirectorReviewees($employee)
    {
        try {
            //all reviewees who under director's control
            $directorReviewees = config('const.director_reviewees');

            if (in_array($employee->id, $directorReviewees) || in_array($employee->department_id, [11,12])) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    /** Update Task's Weight
     *
     * Update task's weight whenever the sticker has been changed
     *
    */
    public static function updateTaskWeight($stickerId, $level, $value)
    {
        try {
            Task::performTransaction(function ($model) use ($stickerId, $level, $value) {
                //find level base on priorities table
                $priority = Priority::where('label', $level)->first();

                //update weight for tasks and task_timings tables but change_weight is true
                $tasks = $model::select('tasks.id', 'tasks.weight','projects.name')
                ->join('task_projects', 'task_projects.task_id', '=', 'tasks.id')
                ->join('projects', 'projects.id', '=', 'task_projects.project_id')
                ->where('change_weight', true)
                ->where('sticker_id', $stickerId)
                ->where('priority', $priority->id)
                ->whereNull('tasks.deleted_at')
                ->whereNull('task_projects.deleted_at')
                ->whereNull('projects.deleted_at')
                ->get();

                foreach ($tasks as $task) {
                    $model::where('id', $task->id)->update(['weight' => $value]);

                    $task->weight = $value;
                    self::updateWeightTaskProjects($task);
                }

                TaskTiming::where('sticker_id', $stickerId)->where('priority', $priority->id)
                        ->update(['weight' => $value]);
            });
        } catch (Exception $e) {
            Log::error($e);
        }
    }

    /** Clear tasks weight after sticker has been deleted
     *
     * Update task's weight whenever the sticker has been changed
     *
    */
    public static function clearTaskWeight($stickerId)
    {
        try {
            Task::performTransaction(function ($model) use ($stickerId) {
                //update weight for tasks and task_timings tables
                $taskIds = Task::select('id')->where('sticker_id', $stickerId)->pluck('id');
                Task::whereIn('id', $taskIds)->update([
                    'weight' => null,
                    'priority' => null,
                    'sticker_id' => null
                ]);

                //update weight for task_projects table
                TaskProject::whereIn('task_id', $taskIds)->update([
                    'weight' => null,
                    'percent' => null,
                ]);

                //update weight for task_timings table
                TaskTiming::where('sticker_id', $stickerId)
                    ->update([
                    'weight' => null,
                    'priority' => null,
                    'sticker_id' => null
                ]);
            });
        } catch (Exception $e) {
            Log::error($e);
        }
    }

    public static function updateWeightTaskProjects($task) : void
    {
        try {
            //update task_projects weight
            // Get all records from the task_projects table
            $taskProjects = TaskProject::where('task_id', $task->id)->get();

            if (is_null($task->weight)) {
                TaskProject::where('task_id', $task->id)->update([
                    'percent' => null,
                    'weight' => null
                ]);
            } else {
                // Compute the new percent and weight values
                $totalPercent = 0;
                $nullPercentCount = 0;
                foreach ($taskProjects as $item) {
                    if (!is_null($item->percent)) {
                        $totalPercent += $item->percent;
                    } else {
                        $nullPercentCount++;
                    }
                }

                $newVal = $task->weight; // The new taskWeighted value
                $nonNullPercentTotalWeight = ($newVal * $totalPercent) / 100;
                $remainingWeight = $newVal - $nonNullPercentTotalWeight;
                $remainingPercent = 100 - $totalPercent;

                if ($nullPercentCount > 0 && $remainingPercent > 0) {
                    $additionalWeightPerNullPercent = $remainingWeight / $nullPercentCount;
                    $additionalPercentPerNullPercent = $remainingPercent / $nullPercentCount;

                    foreach ($taskProjects as $value) {
                        if (is_null($value->percent)) {
                            $value->weight = $additionalWeightPerNullPercent;
                            $value->percent = $additionalPercentPerNullPercent;
                        } else {
                            $value->weight = ($newVal * $value->percent) / 100;
                        }
                        $value->save(); // Save the updated record to the database
                    }
                } else {
                    foreach ($taskProjects as $record) {
                        $record->weight = ($newVal * $record->percent) / 100;
                        $record->save(); // Save the updated record to the database
                    }
                }
            }
        } catch (Exception $e) {
            Log::error($e);
        }
    }

    public static function createUpFileFolders($id, $fileFolder) : void
    {
        $fileFolderPath = public_path($fileFolder.'/'.$id);

        //create file folder
        File::ensureDirectoryExists(dirname($fileFolderPath));
        if (!is_dir($fileFolderPath)) {
            File::makeDirectory($fileFolderPath);
        }
    }

    public static function getDepartmentsJob()
    {
        try {
            // $departments = config('const.departments_with_job');
            $departments = Department::select(
                'departments.id as value',
                'departments.name as label',
                'departments.note',
                'departments.company_id',
                'departments.short_name as short_name',
                'departments.active_job',
            )
            ->whereNull('departments.deleted_at')
            ->where('departments.active_job', 1)
            ->orderBy('departments.id', 'asc')
            ->get()->toArray();
            $filteredDepartments = array_values(array_filter($departments, function ($department) {
                $userDepartmentId = Auth()->user()->department_id;
                return ($userDepartmentId == 12) ? ($department['value'] == 12) : ($department['value'] != 12);
            }));

            return $filteredDepartments;
        } catch (Exception $e) {
            Log::error($e);
        }
    }

    public static function loadTimesheetData($review)
    {
        try {
            $employeeId = $review->employee_id;
            $startDate = Review::where('employee_id', $employeeId)->max('start_date');
            $endDate = Carbon::now()->format('Y-m-d');
            if (!$startDate) {
                $employee = User::select('created_at')->where('id', $employeeId)->get();
                $startDate = Carbon::create($employee->created_at)->format('Y-m-d');
            }

            $requestDatas['start_date'] = $startDate;
            $requestDatas['end_date'] = $endDate;
            $requestDatas['user_id'] = $employeeId;

            $data = self::getTimesheetReport($requestDatas);
        } catch (Exception $e) {
            Log::error($e);
        }
    }

    public static function getTimesheetReport($requestDatas)
    {
        try {
            $user = Auth()->user();
            $startTimeAM = config('const.start_time_am');
            $endTimeAM = config('const.end_time_am');
            $startTimePM = config('const.start_time_pm');
            $endTimePM = config('const.end_time_pm');

            $holidays = Holiday::query()
            ->whereDate(
                'holidays.start_date',
                '<=',
                Carbon::create($requestDatas['end_date'])->format('Y-m-d')
            )
            ->whereDate(
                'holidays.end_date',
                '>=',
                Carbon::create($requestDatas['start_date'])->format('Y-m-d')
            )
            ->get();

            $holidayOffsets = HolidayOffset::select('offset_date')
            ->whereBetween('offset_date', [
                Carbon::parse($requestDatas['start_date'])->format('Y-m-d'),
                Carbon::parse($requestDatas['end_date'])->format('Y-m-d')
            ])
            ->pluck('offset_date')->toArray();

            $requestEndDate = $requestDatas['end_date'];
            if (isset($requestDatas['timesheet_report']) && $requestDatas['timesheet_report'] === true) {
                $endDateCarbon = Carbon::createFromFormat('Y/m/d', $requestEndDate);
                // $yesterday = Carbon::yesterday();

                // if ($endDateCarbon->greaterThan($yesterday)) {
                //     $requestEndDate = $yesterday->format('Y/m/d');
                // }
            }
            $employees = User::select('id', 'fullname', 'user_code', 'date_official','department_id','avatar','created_at')
            ->with(['timesheets' => function ($query) use ($requestDatas, $requestEndDate) {
                $checkInTime = Carbon::createFromTime(5, 0)->format('H:i:s'); // Create and format time string
                $query->select('date', 'user_code', DB::raw('MIN(time) as check_in'));
                if (isset($requestDatas['start_date'])) {
                    $query->where('date', '>=', $requestDatas['start_date']);
                }
                if (isset($requestDatas['end_date'])) {
                    $query->where('date', '<=', $requestEndDate);
                }
                $query->whereRaw('EXTRACT(DOW FROM date) <> 0') // Exclude data for Sundays (0 = Sunday)
                ->where('time', '>=', $checkInTime) // Using the formatted time string
                ->groupBy('user_code', 'date');
            }])
            ->with(['checkouts' => function ($query) use ($requestDatas, $requestEndDate) {
                $query->select('date', 'user_code', DB::raw('MAX(check_out) as check_out'), 'final_checkout');
                if (isset($requestDatas['start_date'])) {
                    $query->where('date', '>=', $requestDatas['start_date']);
                }
                if (isset($requestDatas['end_date'])) {
                    $query->where('date', '<=', $requestEndDate);
                }
                $query->whereRaw('EXTRACT(DOW FROM date) <> 0') // Exclude data for Sundays (0 = Sunday)
                ->groupBy('user_code', 'date', 'final_checkout');
            }])
            ->with(['goouts' => function ($query) use ($requestDatas, $requestEndDate) {
                $query->select('user_code', 'date', 'start_time', 'end_time');
                if (isset($requestDatas['start_date'])) {
                    $query->where('date', '>=', $requestDatas['start_date']);
                }
                if (isset($requestDatas['end_date'])) {
                    $query->where('date', '<=', $requestEndDate);
                }
                $query->whereRaw('EXTRACT(DOW FROM date) <> 0'); // Exclude data for Sundays (0 = Sunday)
            }])
            ->with(['petitions' => function ($query) use ($requestDatas, $requestEndDate) {
                $query->where('status', 1)
                        ->where(function ($query) use ($requestDatas, $requestEndDate) {
                            $query->where(function ($query) use ($requestDatas, $requestEndDate) {
                                $query->whereNull('end_date')
                                    ->where('start_date', '>=', $requestDatas['start_date'])
                                    ->where('start_date', '<=', $requestEndDate);
                            })->orWhere(function ($query) use ($requestDatas, $requestEndDate) {
                                $query->whereNotNull('end_date')
                                    ->where('start_date', '<=', $requestEndDate)
                                    ->where('end_date', '>=', $requestDatas['start_date']);
                            });
                        });

                // Add the ORDER BY clause
                $query->orderByRaw("CASE WHEN type = 4 THEN 0 ELSE 1 END, type");
            }])
            ->where(function ($query) use ($requestDatas, $user) {
                if (isset($requestDatas['user_id']) && !is_array($requestDatas['user_id'])) {
                    $query->where('id', $requestDatas['user_id']);
                }else if (isset($requestDatas['user_id']) && is_array($requestDatas['user_id'])) {
                    $query->whereIn('id', $requestDatas['user_id']);
                }
                if (isset($requestDatas['department_id']) && !is_array($requestDatas['department_id'])) {
                    $query->where('department_id', $requestDatas['department_id']);
                }else if (isset($requestDatas['department_id']) && is_array($requestDatas['department_id'])) {
                    $query->whereIn('department_id', $requestDatas['department_id']);
                }else {
                    if (isset($requestDatas['view']) && $requestDatas['view'] == 'home' && $user->position === 1) {
                        $query->where('users.department_id', $user->department_id);
                    }
                    //update id position = 0 get data
                    elseif (isset($requestDatas['view']) && $requestDatas['view'] == 'home' && ($user->position === 2 || in_array($user->id, [161, 63]))) {
                        $query->when($user->department_id == 12, function ($query) use ($user) {
                            $query->where('users.department_id', 12);
                        }, function ($query) use ($user) {
                            $query->whereNot('users.department_id', 12);
                        });
                    }
                }
                $query->where('user_status', 1)->whereNotIn('id', [44,46]);
                if ( !isset($requestDatas['get_user_type_4']) || $requestDatas['get_user_type_4'] == false ) {
                    $query->where('users.type','!=', 4);
                }

                // Accounting and HR can see all employee's timesheet and only accept if there is no cronjob access, type = 'cronjob'
                // Incase the calling is coming from ReportController::getWorkdayReports, we dont need to use this condition, type = 'workday_report
                if (!isset($requestDatas['type'])) {
                    if ($user->position == 1 && $user->permission < 1 && !in_array($user->department_id, [1, 7, 8])) {
                        $query->where('users.department_id', $user->department_id);
                    } elseif ($user->position < 1 && $user->permission < 1 && !in_array($user->department_id, [1, 7, 8])) {
                        // Employee is a member who can see only themselves
                        $query->where('users.id', $user->id);
                    }
                }
            })
            // ->orderByRaw('COALESCE(date_official, created_at) ASC')
            ->orderByRaw('COALESCE(created_at, date_official) ASC')
            ->get();

            $employeesFormatted = $employees->map(function ($employee) use ($holidays, $holidayOffsets, $startTimeAM, $endTimeAM, $startTimePM, $endTimePM) {
                $formattedData = [
                    'id' => $employee->id,
                    'user_code' => $employee->user_code,
                    'fullname' => $employee->fullname,
                    'department_id' => $employee->department_id,
                    'avatar' => $employee->avatar,
                    'date_official' => $employee->date_official ? Carbon::create($employee->date_official)->format('Y/m/d') : '',
                    'created_at'=>$employee->created_at,
                    'timesheets' => [], // Add an empty 'timesheets' array
                ];

                $employee->petitions->each(function ($petition) use ($holidayOffsets, $employee, &$formattedData, $startTimeAM, $endTimeAM, $endTimePM) {
                    $formattedDate = Carbon::parse($petition->start_date)->format('Ymd');
                    // Define more processing functions for other petition types as needed
                    $dispatchTable = [
                        1 => 'processType1Petition',
                        2 => 'processType2Petition',
                        4 => 'processType4Petition',
                        5 => 'processType5Petition',
                        6 => 'processType6Petition',
                        7 => 'processType7Petition',
                        9 => 'processType9Petition',
                    ];

                    if (isset($dispatchTable[$petition->type])) {
                        $processFunction = $dispatchTable[$petition->type];
                        $timesheetData = self::$processFunction($holidayOffsets, $petition, $formattedData, $formattedDate, $startTimeAM, $endTimeAM, $endTimePM);

                        if ($timesheetData !== null) {
                            $formattedData = $timesheetData;

                            if (
                                isset($timesheetData['timesheets'][$formattedDate]['missed_in_out']) ||
                                isset($timesheetData['timesheets'][$formattedDate]['on_business_trip'])
                            ) {
                                $date = Carbon::create($petition->start_date);

                                $checkInData = [
                                    "check_in" => $timesheetData['timesheets'][$formattedDate]['petition_check_in'],
                                    "date" => $date->format('Y-m-d')
                                ];
                                // Convert the array to a PHP object using stdClass
                                $checkInDataObject = (object) $checkInData;
                                // Check if there is no element with "date" => $date in $employee->timesheets
                                if ($employee->timesheets->where('date', $date->format('Y-m-d'))->isEmpty()) {
                                    $employee->timesheets->push($checkInDataObject);
                                }

                                //employees checkouts
                                if (isset($timesheetData['timesheets'][$formattedDate]['petition_check_out'])) {
                                    $checkOutData = [
                                        "check_out" => $timesheetData['timesheets'][$formattedDate]['petition_check_out'],
                                        "date" => $date->format('Y-m-d')
                                    ];
                                    $checkOutDataObject = (object) $checkOutData;
                                    // Check if there is no element with "date" => $date in $employee->checkouts
                                    if ($employee->checkouts->where('date', $date)->isEmpty()) {
                                        $employee->checkouts->push($checkOutDataObject);
                                    }
                                }
                            }
                        }
                    }

                    //except petition employee leave multiple days (type is 2 and type_off is 4)
                    if ($petition->type_off != 4) {
                        // Shorthand variable for 'timesheets' array
                        $timesheets = &$formattedData['timesheets'][$formattedDate];

                        if ($petition->type_off) {
                            // Assign petition employees leave with type off am or pm
                            $timesheets['petition_type_off'] = $petition->type_off;
                        }

                        // Initialize petition_type and petition_type arrays if not already set
                        $timesheets['petition_type'] ??= [];
                        // Push the current petition type to the array
                        $timesheets['petition_type'][] = $petition->type;
                        // Truncate the petition_titles string and add ellipsis if needed
                        $timesheets['petition_title'] = self::truncateString($timesheets, $petition, 1);
                    }
                });

                $employee->timesheets->each(function ($timesheet) use (&$formattedData, $startTimeAM, $endTimeAM, $startTimePM) {
                    $startTimeObject = Carbon::parse($startTimeAM);
                    $formattedDate = Carbon::create($timesheet->date)->format('Ymd');
                    $checkIn = Carbon::parse($timesheet->check_in);

                    $breakLunchInSeconds = 0;
                    if (isset($formattedData['timesheets'][$formattedDate])) {
                        $timesheets = $formattedData['timesheets'][$formattedDate];

                        //update time check in if there is a petition about change timesheets
                        $petitionCheckIn = Carbon::parse($timesheets['petition_check_in'] ?? $checkIn);
                        $checkIn = ($checkIn > $petitionCheckIn || !$checkIn) ? $petitionCheckIn : $checkIn;

                        // Check if petition_type_off exists and has a value of 1
                        // if (isset($timesheets['petition_type_off']) && $timesheets['petition_type_off'] === 1) {
                        if (isset($timesheets['petition_type_off']) && $timesheets['petition_type_off'] === 1 && in_array(2,$timesheets['petition_type'])) {
                            $startTimeObject = Carbon::parse($startTimePM);
                            // Minus lunch time breakout (around 1h30: 5400s)
                            $breakLunchInSeconds = 5400;
                        }
                    }
                    // Calculate the time difference
                    $diffInSeconds = max(0, $checkIn->diffInSeconds($startTimeObject));

                    // Create a new 'timesheet' array for the formatted date
                    $timesheetData = [
                        'check_in' => $checkIn->format('H:i:s'),
                        // 'late_total' => max(0, ($checkIn > $startTimeObject) * $diffInSeconds),
                        // case warrior on saturday afternoon
                        'late_total' => Carbon::create($timesheet->date)->isSaturday() && $startTimeObject == Carbon::parse($startTimePM) ? 0 : max(0, ($checkIn > $startTimeObject) * $diffInSeconds),
                        'go_early_total' => max(0, ($checkIn <= $startTimeObject) * ($diffInSeconds - $breakLunchInSeconds)),
                    ];

                    // Merge the new 'timesheet' array with the existing 'timesheets' array using null coalescing operator
                    $formattedData['timesheets'][$formattedDate] = array_merge($formattedData['timesheets'][$formattedDate] ?? [], $timesheetData);
                });

                $employee->checkouts->each(function ($checkout) use ($holidayOffsets, &$formattedData, $endTimeAM, $startTimePM, $endTimePM) {
                    $formattedDate = Carbon::create($checkout->date)->format('Ymd');
                    $checkOut = Carbon::parse($checkout->check_out);
                    $endTimeAMObject = Carbon::parse($endTimeAM);
                    $startTimePMObject = Carbon::parse($startTimePM);
                    $endTimePMObject = Carbon::parse($endTimePM);

                    if (($formattedData['timesheets'][$formattedDate]['check_in'] ?? null) !== null) {
                        $timesheet = &$formattedData['timesheets'][$formattedDate]; // Assign a reference to the specific timesheet array

                        // Incase the employee requested a petition do extra workday (petition type is 5), we have to modify the $endTimePM value
                        // Base on $petition->end_time
                        if (isset($formattedData['extra_workdays']) && isset($formattedData['extra_workdays'][$formattedDate])) {
                            $endTimePMObject = Carbon::parse($formattedData['extra_workdays'][$formattedDate]);
                        }

                        $petitionCheckOut = Carbon::parse($timesheet['petition_check_out'] ?? $checkOut);
                        // $checkOut = ($checkOut < $petitionCheckOut || !$checkOut) ? $petitionCheckOut : $checkOut;
                        //case petition_check_out > or < checkOut
                        $checkOut = ($petitionCheckOut || !$checkOut) ? $petitionCheckOut : $checkOut;
                        // $checkOut = (($petitionCheckOut || !$checkOut) && $petitionCheckOut > $checkOut) ? $petitionCheckOut : $checkOut;
                        $timesheet['check_out'] = $checkOut->format('H:i:s');

                        $checkIn = Carbon::parse($timesheet['check_in']);
                        $goEarlyTotal = $timesheet['go_early_total'];

                        $totalSecondsOffice = 0;
                        $totalBreakOutSeconds = 0;
                        // Incase the employee requested a petition do extra workday (petition type is 5), and the petitions end_time is 12:00
                        // We will add it in cases the day is Saturday
                        $isHalfDay = self::isHalfDay($holidayOffsets, $formattedDate, $formattedData, $endTimePMObject);
                        if ($isHalfDay) {

                            $diffInSeconds = max(0, $checkOut->diffInSeconds($endTimeAMObject));
                            $timesheet['early_total'] = max(0, ($checkOut <= $endTimeAMObject) * $diffInSeconds);
                            $leaveLateTotal = max(0, ($checkOut > $startTimePMObject) * $diffInSeconds);

                            $totalSecondInLunch = 0;
                            //in case, employees checkout in range 12:00-13:30, the workday wll be minus base on totalSecondInLunch
                            if ($endTimeAMObject < $checkOut && $checkOut <= $startTimePMObject) {
                                $totalSecondInLunch = $diffInSeconds;
                            }
                            if ($leaveLateTotal > 0) {
                                $totalBreakOutSeconds = $startTimePMObject->diffInSeconds($endTimeAMObject);
                                $leaveLateTotal -= $totalBreakOutSeconds;
                            }
                            $timesheet['leave_late_total'] = $leaveLateTotal;

                            $totalSeconds = $checkOut->diffInSeconds($checkIn);
                            $totalSecondsOffice = $totalSeconds - ($goEarlyTotal + $leaveLateTotal + $totalBreakOutSeconds + $totalSecondInLunch);
                        } else {
                            $diffInSeconds = max(0, $checkOut->diffInSeconds($endTimePMObject));
                            $timesheet['early_total'] = max(0, ($checkOut <= $endTimePMObject) * $diffInSeconds);
                            $leaveLateTotal = max(0, ($checkOut > $endTimePMObject) * $diffInSeconds);
                            $timesheet['leave_late_total'] = $leaveLateTotal;

                            if ($checkOut >= $startTimePMObject) {
                                $totalBreakOutSeconds = $startTimePMObject->diffInSeconds($endTimeAMObject);
                            }
                            //break out time will handle if there is a petition employee leave a half day am and check in inrange 12:00->13:30
                            if ($endTimeAMObject <= $checkIn && $checkIn <= $startTimePMObject) {
                                $totalBreakOutSeconds = $startTimePMObject->diffInSeconds($checkIn);
                            } else if ($checkIn >= $startTimePMObject) {
                                $totalBreakOutSeconds = 0;
                            }
                            $totalSeconds = $checkOut->diffInSeconds($checkIn);
                            $totalSecondsOffice = $totalSeconds - $goEarlyTotal - $leaveLateTotal - $totalBreakOutSeconds;
                        }

                        // Calculate the remaining office time if 'office_time_goouts' exists
                        $timeGoOutInOffice = 0;
                        if (isset($timesheet['office_time_goouts'])) {
                            $timeGoOutInOffice = $timesheet['office_time_goouts'];
                            if ($checkOut <= Carbon::parse($timesheet['start_time_goouts'])) {
                                $timeGoOutInOffice = 0;
                            }

                            unset($timesheet['start_time_goouts']);
                            unset($timesheet['end_time_goouts']);
                        }

                        $remainingOfficeTime = $totalSecondsOffice - $timeGoOutInOffice;
                        $roundedWorkday = $remainingOfficeTime/60/60/8;
                        // Incase employee request a petition leave early or go late but the time check_in/out is not leave early or go late
                        // We have to minus it to workday
                        $minusWorkday = 0;
                        if ($timesheet['early_total'] === 0 && isset($timesheet['is_petition_leave_early'])) {
                            $timesheet['early_total'] = $timesheet['petition_late_early_time'];
                            $minusWorkday = $timesheet['early_total'];
                        } elseif ($timesheet['late_total'] === 0 && isset($timesheet['is_petition_go_late'])) {
                            $timesheet['late_total'] = $timesheet['petition_late_early_time'];
                            $minusWorkday = $timesheet['late_total'];
                        }
                        $minusWorkday = $minusWorkday > 0 ? $minusWorkday/60/60/8 : 0;

                        $timesheet['workday_original'] = round(max(0,$roundedWorkday), 3) ;
                        $timesheet['workday'] = round(max(0, ($timesheet['workday'] ?? 0) + $roundedWorkday - $minusWorkday), 3);
                        $timesheet['final_checkout'] = empty($checkout->final_checkout) ? false : $checkout->final_checkout;

                        if (isset($timesheet['petition_type']) && in_array(6,$timesheet['petition_type'])) {
                            $timesheet['workday'] = 0;
                        }

                    }
                });

                $employee->goouts->each(function ($goout) use ($holidayOffsets, &$formattedData, $startTimeAM, $endTimeAM, $startTimePM, $endTimePM) {
                    $date = Carbon::create($goout->date);
                    $formattedDate = $date->format('Ymd');

                    if (isset($formattedData['timesheets'][$formattedDate])) {
                        $startTimeAMObject = Carbon::parse($startTimeAM);
                        $endTimeAMObject = Carbon::parse($endTimeAM);
                        $startTimePM = Carbon::parse($startTimePM);
                        $timesheet = &$formattedData['timesheets'][$formattedDate];

                        $startTime = Carbon::parse($goout->start_time);
                        //detect employees are going out at current time
                        $now = Carbon::now();
                        if ($startTime <= $now && $now->format('Ymd') == $formattedDate) {
                            $timesheet['is_going_out'] = true;
                        }
                        if ($goout->end_time || isset($timesheet['check_out'])) {
                            $officeHourSATRange = [
                                Carbon::SATURDAY => [
                                    'start' => $startTimeAMObject,
                                    'end' => $endTimeAMObject,
                                ],
                            ];
                            $endTime = $goout->end_time ? Carbon::parse($goout->end_time) : Carbon::parse($timesheet['check_out']);

                            //detect employees are going out at current time
                            if ($now > $endTime && $now->format('Ymd') == $formattedDate) {
                                unset($timesheet['is_going_out']);
                            }

                            // Determine the day's office hour range
                            $officeHours = (
                                isset($officeHourSATRange[$date->dayOfWeek]) && !in_array($date->format('Y-m-d'), $holidayOffsets)
                            ) ? $officeHourSATRange[$date->dayOfWeek] : [
                                'start' => $startTimeAMObject,
                                'end' => Carbon::parse($endTimePM),
                            ];
                            // Calculate the time difference in seconds
                            $time = self::handleTimeGoOut($officeHours, $startTime, $endTime, $endTimeAMObject, $startTimePM);
                            if ($time['in_office'] > 0) {
                                $timesheet['office_goouts'] = ($timesheet['office_goouts'] ?? 0) + 1;
                                $timesheet['office_time_goouts'] = ($timesheet['office_time_goouts'] ?? 0) + $time['in_office'];

                                $timesheet['workday'] = round(max(0, ($timesheet['workday'] ?? 0) - $time['in_office']/60/60/8), 3);
                            } elseif ($time['not_in_office'] > 0) {
                                $timesheet['non_office_goouts'] = ($timesheet['non_office_goouts'] ?? 0) + 1;
                                $timesheet['non_office_time_goouts'] = ($timesheet['non_office_time_goouts'] ?? 0) + $time['not_in_office'];

                                $timesheet['click_time_goouts'] = ($timesheet['click_time_goouts'] ?? 0) + $time['not_in_office'];
                            }
                        }
                    }
                });

                $holidays->each(function ($holiday) use (&$formattedData) {
                    $startDate = Carbon::parse($holiday->start_date);
                    $endDate = Carbon::parse($holiday->end_date);
                    $detail = [
                        'is_holiday' => true,
                        'holiday_title' => $holiday->name
                    ];

                    while ($startDate <= $endDate) {
                        $formattedDate = $startDate->format('Ymd');

                        if (
                            !$startDate->isSunday() && 
                            (!isset($formattedData['extra_workdays']) || !isset($formattedData['extra_workdays'][$formattedDate])) &&
                            !isset($formattedData['timesheets'][$formattedDate]['extra_warrior_time'])
                        ) {
                            if (isset($formattedData['timesheets'][$formattedDate]['long_leave'])) {
                                $formattedData['timesheets'][$formattedDate]['is_holiday'] = $detail['is_holiday'];
                                $formattedData['timesheets'][$formattedDate]['holiday_title'] = $detail['holiday_title'];
                            } else {
                                $formattedData['timesheets'][$formattedDate] = $detail;
                            }
                        }

                        $startDate->addDay();
                    }
                });

                return $formattedData;
            })->values()->all();

            return $employeesFormatted;
        } catch (Exception $e) {
            Log::error($e);
        }
    }

    //handle petition go late or leave early
    private static function processType1Petition($holidayOffsets, $petition, &$formattedData, $formattedDate, $startTimeAM, $endTimeAM, $endTimePM)
    {
        $startTimeObject = Carbon::parse($petition->start_time);
        $endTimeObject = Carbon::parse($petition->end_time);

        $finishTime = $endTimePM;
        if (self::isSatHolidayOffset($petition->start_date, $holidayOffsets)) {
            $finishTime = $endTimeAM;
        }

        $formattedData['timesheets'][$formattedDate] ??= [];
        $formattedData['timesheets'][$formattedDate]['petition_late_early_time'] = $endTimeObject->diffInSeconds($startTimeObject);
        if ($startTimeObject->format('H:i:s') === $startTimeAM) {
            $formattedData['timesheets'][$formattedDate]['is_petition_go_late'] = true;
        } elseif ($endTimeObject->format('H:i:s') === $finishTime) {
            $formattedData['timesheets'][$formattedDate]['is_petition_leave_early'] = true;
            $formattedData['timesheets'][$formattedDate]['petition_check_out'] = $petition->start_time;
        }

        return $formattedData;
    }

    //handle petition leave of work
    private static function processType2Petition($holidayOffsets, $petition, &$formattedData, $formattedDate, $startTimeAM, $endTimeAM, $endTimePM)
    {
        $typeMappings = [
            1 => ['workday' => 0],
            2 => ['workday' => 0],
            3 => ['long_leave' => 1],
            4 => ['long_leave' => 2],
        ];

        $type = $petition->type_off;
        if ($petition->type_paid == 1) {
            $typeMappings[1]['workday'] = 0.5;
            $typeMappings[2]['workday'] = 0.5;

            $typeMappings[3]['petition_check_in'] = $startTimeAM;
            $typeMappings[3]['petition_check_out'] = Carbon::parse($petition->start_date)->isSaturday() ? $endTimeAM : $endTimePM;

            $typeMappings[4]['petition_check_in'] = $startTimeAM;
            $typeMappings[4]['petition_check_out'] = Carbon::parse($petition->start_date)->isSaturday() ? $endTimeAM : $endTimePM;

            // Use array_map to add 'is_paid_leave' => true to each element
            $typeMappings = array_map(function ($element) {
                $element['is_paid_leave'] = true;

                return $element;
            }, $typeMappings);
        }

        if (isset($typeMappings[$type])) {
            $formattedData['timesheets'][$formattedDate] ??= [];

            // Increment the workday value for type 1 and 2 petitions
            if (in_array($type, [1, 2])) {
                $formattedData['timesheets'][$formattedDate]['workday'] = collect($formattedData['timesheets'][$formattedDate]['workday'] ?? [])
                    ->push($typeMappings[$type]['workday'])
                    ->sum();

                // Add 'long_leave' => 1 if there are more than 1 petitions on the same day
                if (isset($formattedData['timesheets'][$formattedDate]['has_type_off_1_and_2'])) {
                    $formattedData['timesheets'][$formattedDate]['long_leave'] = 1;
                }

                // Use a flag variable to check if a type 1 or type 2 petition already exists
                $formattedData['timesheets'][$formattedDate]['has_type_off_1_and_2'] = true;

                // Add 'is_paid_leave' from $typeMappings to $formattedData if it's set
                if (isset($typeMappings[$type]['is_paid_leave'])) {
                    $formattedData['timesheets'][$formattedDate]['is_paid_leave'] = true;
                }
            } elseif ($type == 3) {
                $formattedData['timesheets'][$formattedDate] = $typeMappings[$type];
            } elseif ($type == 4) {
                $startDate = Carbon::parse($petition->start_date);
                $endDate = Carbon::parse($petition->end_date);

                // Unset the start date that was added as it will be duplicated
                unset($formattedData['timesheets'][$startDate->format('Ymd')]);

                while ($startDate <= $endDate) {
                    if (!$startDate->isSunday()) {
                        $formattedDate = $startDate->format('Ymd');
                        $formattedData['timesheets'][$formattedDate] = $typeMappings[$type];

                        if ($startDate->isSaturday()) {
                            $formattedData['timesheets'][$formattedDate]['petition_check_out'] = $endTimeAM;
                        }
                    }

                    $startDate->addDay();
                }
            }
        }

        return $formattedData;
    }

    //handle petition change timesheets
    private static function processType4Petition($holidayOffsets, $petition, &$formattedData, $formattedDate, $startTimeAM, $endTimeAM, $endTimePM)
    {
        $formattedData['timesheets'][$formattedDate] = [
            'petition_check_in' => $petition->start_time_change
        ];

        if ($petition->end_time_change) {
            $formattedData['timesheets'][$formattedDate]['petition_check_out'] = $petition->end_time_change;
        }

        if (!$petition->start_time && !$petition->end_time) {
            $formattedData['timesheets'][$formattedDate]['missed_in_out'] = true;
        }

        return $formattedData;
    }

    //handle petition request do extra workday
    private static function processType5Petition($holidayOffsets, $petition, &$formattedData, $formattedDate, $startTimeAM, $endTimeAM, $endTimePM)
    {
        $formattedData['extra_workdays'][$formattedDate] = $petition->end_time;

        return $formattedData;
    }

    //handle petition request do warrior time
    private static function processType6Petition($holidayOffsets, $petition, &$formattedData, $formattedDate, $startTimeAM, $endTimeAM, $endTimePM)
    {
        // Convert your datetime values to Carbon objects
        $startTimeObject = Carbon::parse($petition->start_time);
        $endTimeObject = Carbon::parse($petition->end_time);

        // Calculate the difference in seconds
        $secondsDifference = $startTimeObject->diffInSeconds($endTimeObject);
        // Check if $endTimeObject is greater than or equal to '13:30 PM'
        if ($endTimeObject->greaterThanOrEqualTo(Carbon::parse('13:30:00'))) {
            $secondsDifference = $secondsDifference - 5400;
        }
        $formattedData['timesheets'][$formattedDate]['extra_warrior_time'] = $secondsDifference;

        return $formattedData;
    }

    //handle petition go out
    private static function processType7Petition($holidayOffsets, $petition, &$formattedData, $formattedDate, $startTimeAM, $endTimeAM, $endTimePM)
    {
        $timesheet = &$formattedData['timesheets'];
        $timesheet[$formattedDate] ??= [];

        $timesheet[$formattedDate]['office_goouts'] = $timesheet[$formattedDate]['office_goouts'] ?? 0;
        $timesheet[$formattedDate]['office_goouts'] += 1;

        $isSat = self::isSatHolidayOffset($formattedDate, $holidayOffsets);
        $startTimeObject = Carbon::parse($startTimeAM);
        $endTimeObject = $isSat ? Carbon::parse($endTimeAM) : Carbon::parse($endTimePM);

        $startTime = Carbon::parse($petition->start_time);
        $endTime = Carbon::parse($petition->end_time);

        // Define the fixed range for 12:00:00 to 13:30:00
        $fixedStartTime = Carbon::createFromTime(12, 0, 0);
        $fixedEndTime = Carbon::createFromTime(13, 30, 0);

        // Calculate the difference in seconds, taking care of the negative case
        $diffInSeconds = max(0, $endTime->diffInSeconds($startTime));
        // Check if the time range goes beyond the fixed range
        if ($startTime < $fixedStartTime && $endTime > $fixedEndTime) {
            // Minus lunch time breakout (around 1h30: 5400s)
            $diffInSeconds -= 5400;
        }

        if ($petition->type_go_out === 1 ) {
            // Update the 'office_time_goouts' value using null coalescing assignment
            $timesheet[$formattedDate]['office_time_goouts'] ??= 0;
            $timesheet[$formattedDate]['office_time_goouts'] += $diffInSeconds;

            $timesheet[$formattedDate]['start_time_goouts'] = $startTime->format('H:i:s');
            $timesheet[$formattedDate]['end_time_goouts'] = $endTime->format('H:i:s');
        } else if ($petition->type_go_out === 0) {
            $timesheet[$formattedDate]['office_time_goouts'] = 0;
            // $timesheet[$formattedDate]['office_time_goouts'] += $diffInSeconds;

            $timesheet[$formattedDate]['start_time_goouts'] = $startTime->format('H:i:s');
            $timesheet[$formattedDate]['end_time_goouts'] = $endTime->format('H:i:s');
        } else {
            // Update the 'non-office_time_goouts' value using null coalescing assignment
            $timesheet[$formattedDate]['non_office_time_goouts'] ??= 0;
            $timesheet[$formattedDate]['non_office_time_goouts'] += $diffInSeconds;
        }

        $cnt = 0;
        if ($startTime <= $startTimeObject) {
            $timesheet[$formattedDate]['petition_check_in'] = $startTime->format('H:i:s');
            $cnt ++;
        }
        if ($endTime >= $endTimeObject) {
            $timesheet[$formattedDate]['petition_check_out'] = $endTime->format('H:i:s');
            $cnt ++;
        }

        if ($cnt == 2) {
            $timesheet[$formattedDate]['is_out_a_day'] = true;
            $timesheet[$formattedDate]['workday'] = $isSat ? 0.5 : 1;
        }

        //detect employees are going out at current time
        $now = Carbon::now();
        if ($startTime <= $now && $now <= $endTime && $now->format('Ymd') == $formattedDate) {
            $timesheet[$formattedDate]['is_going_out'] = true;
        }

        return $formattedData;
    }

    //handle petition on a business trip
    private static function processType9Petition($holidayOffsets, $petition, &$formattedData, $formattedDate, $startTimeAM, $endTimeAM, $endTimePM)
    {
        $formattedData['timesheets'][$formattedDate] ??= [];

        $formattedData['timesheets'][$formattedDate] = [
            'petition_check_in' => $petition->start_time,
            'petition_check_out' => $petition->end_time,
            'on_business_trip' => true
        ];

        return $formattedData;
    }

    private static function isHalfDay($holidayOffsets, $formattedDate, $formattedData, $endTimePMObject)
    {
        return self::isSatHolidayOffset($formattedDate, $holidayOffsets) ||
            (isset($formattedData['timesheets'][$formattedDate]['petition_type_off']) &&
                $formattedData['timesheets'][$formattedDate]['petition_type_off'] === 2) ||
            $endTimePMObject->format('H:i') === '12:00';
    }

    private static function isSatHolidayOffset($formattedDate, $holidayOffsets)
    {
        $formattedDateYmd = Carbon::parse($formattedDate)->format('Y-m-d');
        return Carbon::parse($formattedDate)->isSaturday() && !in_array($formattedDateYmd, $holidayOffsets);
    }

    private static function handleTimeGoOut($officeHours, $startTime, $endTime, $endTimeAMObject, $startTimePM)
    {
        $timeInOffice = 0;
        $timeNotInOffice = 0;
        if ($startTime->lte($endTimeAMObject) && $endTime->between($endTimeAMObject, $startTimePM)) {
            $timeInOffice = $endTimeAMObject->diffInSeconds($startTime);
            $timeNotInOffice = 0;
        }
        elseif ($startTime->lte($endTimeAMObject) && $endTime->gte($startTimePM)) {
            $timeInOffice = $endTimeAMObject->diffInSeconds($startTime);
            $timeNotInOffice = $endTime->diffInSeconds($startTimePM);
        }
        elseif ($startTime->between($officeHours['start'], $endTimeAMObject) && $endTime->between($officeHours['start'], $endTimeAMObject)) {
            $timeInOffice = $endTime->diffInSeconds($startTime);
            $timeNotInOffice = 0;
        }
        elseif ($startTime->between($endTimeAMObject, $startTimePM) && $endTime->between($endTimeAMObject, $startTimePM)) {
            $timeInOffice = 0;
            $timeNotInOffice = 0;
        }
        elseif ($startTime->between($endTimeAMObject, $startTimePM) && $endTime->gte($startTimePM)) {
            $timeInOffice = 0;
            $timeNotInOffice = $endTime->diffInSeconds($startTimePM);
        }
        elseif ($startTime->between($startTimePM, $officeHours['end']) && $endTime->between($startTimePM, $officeHours['end'])) {
            $timeInOffice = $endTime->diffInSeconds($startTime);
            $timeNotInOffice = 0;
        }
        elseif ($startTime->lte($officeHours['start']) && $endTime->lte($officeHours['start'])) {
            $timeInOffice = 0;
            $timeNotInOffice = $endTime->diffInSeconds($startTime);
        }
        elseif ($startTime->gte($officeHours['end']) && $endTime->gte($officeHours['end'])) {
            $timeInOffice = 0;
            $timeNotInOffice = $endTime->diffInSeconds($startTime);
        }
        else {
            $timeInOffice = $endTime->diffInSeconds($startTime);
        }

        return [
            'in_office' => $timeInOffice,
            'not_in_office' => $timeNotInOffice
        ];
    }

    public static function getWarriorTitle($requestDatas, $effortTime)
    {
        $currentDate = Carbon::now();
        if (isset($requestDatas['type']) && $requestDatas['type'] == 'cronjob') {
            $user = User::findOrFail($requestDatas['user_id']);
            $userDateOfficial = $user->date_official;
        } else {
            $userDateOfficial = Auth()->user()->date_official;
        }
        $diffInYears = $userDateOfficial ? $currentDate->diffInYears($userDateOfficial) : 0;

        $workdayInRange = CommonController::getWorkDayInRange($requestDatas);
        $warrior = $workdayInRange['regular_workday'];

        $warriorMultiplier = ($diffInYears >= 3) ? 1 : 2;

        $level1 = $warrior * $warriorMultiplier;
        $level2 = $warrior * ($warriorMultiplier + 1);
        $level3 = $warrior * ($warriorMultiplier + 2);

        if ($effortTime >= $level1 && $effortTime < $level2) {
            return 'Warrior 1';
        } elseif ($effortTime >= $level2 && $effortTime < $level3) {
            return 'Warrior 2';
        } elseif ($effortTime >= $level3) {
            return 'Warrior 3';
        }

        return 'Soldier';
    }

    public static function getWorkDayInRange($requestDatas)
    {
        $holidays = Holiday::query()
        ->whereDate(
            'holidays.start_date',
            '<=',
            Carbon::create($requestDatas['end_date'])->format('Y-m-d')
        )
        ->whereDate(
            'holidays.end_date',
            '>=',
            Carbon::create($requestDatas['start_date'])->format('Y-m-d')
        )
        ->get();

        $workDays = self::countWorkDay($requestDatas, $holidays);

        return $workDays;
    }

    public static function getWorkTime($requestDatas)
    {
        try {
            $tasks = Task::join('users', function ($join) {
                $join->on('users.id', '=', 'tasks.user_id')->where('users.position', '<=', 2)
                ->where('users.user_status', 1);
            })
            ->join('task_timings', function ($join) use ($requestDatas) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at')->whereNull('task_timings.task_assignment_id')
                ->whereBetween('task_timings.work_date', [
                    Carbon::parse($requestDatas['start_date'])->startOfDay(),
                    Carbon::parse($requestDatas['end_date'])->endOfDay(),
                ]);
            })
            ->select(
                'users.id',
                'users.fullname',
                'task_timings.work_date',
                DB::raw("SUM(task_timings.time_spent) as total_time_spent")
            )
            ->when(!empty($requestDatas['department_id']), function ($query) use ($requestDatas) {
                $query->where('users.department_id', $requestDatas['department_id']);
            })
            ->when(!empty($requestDatas['user_id']), function ($query) use ($requestDatas) {
                $query->where('users.id', $requestDatas['user_id']);
            })
            ->groupBy('users.id', 'task_timings.work_date')
            ->get();

            $task_assignments = TaskAssignment::join('users', function ($join) {
                $join->on('users.id', '=', 'task_assignments.assigned_user_id')->where('users.position', '<=', 2)
                ->where('users.user_status', 1);
            })
            ->join('task_timings', function ($join) use ($requestDatas) {
                $join->on('task_assignments.id', '=', 'task_timings.task_assignment_id')->whereNull('task_timings.deleted_at')
                ->whereBetween('task_timings.work_date', [
                    Carbon::parse($requestDatas['start_date'])->startOfDay(),
                    Carbon::parse($requestDatas['end_date'])->endOfDay(),
                ]);
            })
            ->select(
                'users.id',
                'users.fullname',
                'task_timings.work_date',
                DB::raw("SUM(task_timings.time_spent) as total_time_spent")
            )
            ->when(!empty($requestDatas['department_id']), function ($query) use ($requestDatas) {
                $query->where('users.department_id', $requestDatas['department_id']);
            })
            ->when(!empty($requestDatas['user_id']), function ($query) use ($requestDatas) {
                $query->where('users.id', $requestDatas['user_id']);
            })
            ->groupBy('users.id', 'task_timings.work_date')
            ->get();

            $combinedResult = $task_assignments->concat($tasks);

            $report = collect($combinedResult)->groupBy('id')->map(function ($group) {
                return $group->reduce(function ($mergedElement, $item) {
                    $formattedWorkDate = Carbon::parse($item['work_date'])->format('Ymd');
                    $mergedElement['worktimes'][$formattedWorkDate]['time'] = round(($mergedElement['worktimes'][$formattedWorkDate]['time'] ?? 0) + $item['total_time_spent'], 2);
                    $mergedElement['fullname'] = $item['fullname'];
                    $mergedElement['id'] = $item['id'];
                    return $mergedElement;
                }, []);
            })->values()->all();

            return $report;
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public static function updateDeadlineToTask($deadline)
    {
        try {
            //update deadline to Task
            $item = TaskDeadline::where('task_id', $deadline->task_id)
                ->selectRaw(' MAX(GREATEST(actual_date, estimate_date)) AS deadline')
                ->groupBy('task_id')
                ->first();

            $task = Task::findOrFail($deadline->task_id);
            $task->deadline = $item->deadline;
            $task->save();
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getUserType()
    {
        try {
            //const users types
            $userTypes = config('const.user_type');

            $userTypes = array_map(function ($id, $name) {
                return ['id' => $id, 'name' => $name];
            }, array_keys($userTypes), $userTypes);

            return response()->json($userTypes);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    private static function countWorkDay($requestDatas, $holidays)
    {
        $regularWorkday = 0;
        $holidayWorkday = 0;

        $requestStartDate = Carbon::createFromFormat('Y/m/d', $requestDatas['start_date'])->startOfDay();
        $requestEndDate = Carbon::createFromFormat('Y/m/d', $requestDatas['end_date'])->endOfDay();

        $period = CarbonPeriod::create($requestStartDate, $requestEndDate);

        foreach ($period->toArray() as $item) {
            // Check if the current date is within any holiday range
            $isHoliday = false;

            foreach ($holidays as $holiday) {
                if ($item->isBetween($holiday->start_date, $holiday->end_date)) {
                    $isHoliday = true;
                    break; // No need to check further if a holiday is found
                }
            }

            //do count
            switch ($item->format('l')) {
                case 'Monday':
                case 'Tuesday':
                case 'Wednesday':
                case 'Thursday':
                case 'Friday':
                    $regularWorkday += $isHoliday ? 0 : 1;
                    $holidayWorkday += $isHoliday ? 1 : 0;
                    break;
                case 'Saturday':
                    $regularWorkday += $isHoliday ? 0 : 0.5;
                    $holidayWorkday += $isHoliday ? 0.5 : 0;
                    break;
                default:
                    // Other cases or holidays not handled by the previous condition
                    break;
            }
        }

        $data = [
            'regular_workday' => $regularWorkday,
            'holiday_workday' => $holidayWorkday
        ];

        return $data;
    }

    // Helper function to truncate a string and add ellipsis if needed
    private static function truncateString(&$timesheets, $petition, $maxItems)
    {
        $petitionLabels = config('const.petition_type');

        $title = '';
        if (count($timesheets['petition_type']) > 1) {
            $title = $timesheets['petition_title'] . ', ...';
        } else {
            $title = self::getPetitionTypeName($petitionLabels, $petition->type);
            if ($petition->type === 2) {
                $title .= self::getCombinedTypeName($petition->type_off, $petition->type_paid);
            }
        }

        return $title;
    }

    private static function getPetitionTypeName($petitionLabels, $id)
    {
        $names = array_column($petitionLabels, "name");
        $key = array_search($id, array_column($petitionLabels, "id"));

        return ($key !== false) ? $names[$key] : null;
    }

    private static function getCombinedTypeName($typeOff, $typePaid)
    {
        $petitionTimePeriodNames = config('const.petition_time_period');

        $typeOffName = self::getPetitionTypeName($petitionTimePeriodNames, $typeOff);

        return ' ' . lcfirst($typeOffName);
    }
    public function getWarriorName()
    {
        try {
            $warrior  = config('const.warror_title');
            $data = array_map(function ($id, $name) {
                return ['id' => $id, 'name' => $name];
            }, array_keys($warrior), $warrior);

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public static function getDepartmentNameAfterQuery($data){
        $departments = config('const.departments');
        $data = $data->map(function ($item) use ($departments) {
            if ($item->department_id != null) {
                $item->department_id = $departments[$item->department_id];
            }

            return $item;
        });
        return $data;
    }
    public static function getUserLogin()
    {
        try {
            $data = [
                'id' => Auth()->user()->id,
                'department_id' => Auth()->user()->department_id,
                'position' => Auth()->user()->position
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
    public static function getUserGender()
    {
        try {
            $gender = config('const.gender');

            return response()->json($gender);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public static function getTotalTimesheetReport($requestDatas) {
        try {
            $resultArray = [];
            $employees = self::getTimesheetReport($requestDatas);
            
            $holidayOffsets = HolidayOffset::select('id', 'offset_date', 'holiday_id', 'workday')
                ->whereBetween('offset_date', [$requestDatas['start_date'], $requestDatas['end_date']])->get();

            // Iterate through each employee and calculate the data
            collect($employees)->each(function ($employee) use (&$resultArray, $holidayOffsets,$requestDatas) {
                $goEarlySum = 0;
                $leaveLateSum = 0;

                $lateSum = 0;
                $lateCount = 0;
                $peLateCount = 0;

                $earlySum = 0;
                $earlyCount = 0;
                $peEarlyCount = 0;

                $extraWarriorTime = 0;

                $extraWorkday = 0;
                $paidWorkday = 0;

                $gooutSum = 0;
                $gooutCount = 0;
                $nonOfficeGooutCount = 0;
                $nonOfficeGooutSum = 0;

                $paidLeaveSum = 0;
                $unPaidLeaveSum = 0;

                $lateSumNonePetition = 0;
                $leaveHoliday = 0;

                $workdayHoliday = 0;
                
                // Iterate through each row in the employee's timesheets
                foreach ($employee['timesheets'] as $date => $timesheet) {
                    if (Carbon::parse((string)$date)->format('Y/m/d') >= $requestDatas['start_date'] && Carbon::parse((string)$date)->format('Y/m/d') <= $requestDatas['end_date']) {
                        // Check the condition and update sum and count petitions go late
                        $isPeGoLate = isset($timesheet['is_petition_go_late']) && $timesheet['is_petition_go_late'];
                        $isLate = isset($timesheet['late_total']) && $timesheet['late_total'] > 0;
                        $peLateCount += $isPeGoLate ? 1 : 0;
                        $lateCount += $isLate && !$isPeGoLate ? 1 : 0;
                        $lateSum += $isLate ? $timesheet['late_total'] : 0;
                        $lateSumNonePetition += $isLate && !$isPeGoLate ? $timesheet['late_total'] : 0;

                        // Check the condition and update sum and count petitions leave early
                        $isPeLeaveEarly = isset($timesheet['is_petition_leave_early']) && $timesheet['is_petition_leave_early'];
                        $isLeaveEarly = isset($timesheet['early_total']) && $timesheet['early_total'] > 0;
                        $peEarlyCount += $isPeLeaveEarly ? 1 : 0;
                        $earlyCount += $isLeaveEarly && !$isPeLeaveEarly ? 1 : 0;
                        $earlySum += $isLeaveEarly ? $timesheet['early_total'] : 0;

                        // Check the condition and update sum and count employees personals goout with petitions and button in offices time
                        $gooutCount += $timesheet['office_goouts'] ?? 0;
                        $gooutSum += $timesheet['office_time_goouts'] ?? 0;
                        $nonOfficeGooutCount += $timesheet['non_office_goouts'] ?? 0;
                        $nonOfficeGooutSum += $timesheet['non_office_time_goouts'] ?? 0;

                        // Check if 'petition_type' exists and contains the value 2 (petitions leave)
                        $isExistPeType = isset($timesheet['petition_type']) && in_array(2, $timesheet['petition_type']);
                        $leaveCount = (isset($timesheet['long_leave']) && (in_array($timesheet['long_leave'], [1,2]))) ? 1 : 0.5;
                        // Check if the holiday's id exists in $holidayOffsets
                        $matchingOffset = $holidayOffsets->firstWhere('offset_date', Carbon::parse((string)$date)->format('Y-m-d'));
                        // case 0.5 paid_leave and 0.5 un_paid_leave
                        if (isset($timesheet['is_paid_leave']) && $isExistPeType && isset($timesheet['long_leave']) && $timesheet['long_leave'] == 1 && in_array($timesheet['petition_type_off'], [1,2] ) ) {
                            $paidLeaveSum += 0.5;
                            $unPaidLeaveSum += 0.5;
                        } else if (isset($timesheet['is_paid_leave']) && ($isExistPeType || $leaveCount === 1)) {

                            $paidLeaveSum += Carbon::parse((string)$date)->isSaturday() && isset($timesheet['long_leave']) && (in_array($timesheet['long_leave'], [2])) ? 0.5 : $leaveCount;
                            // $paidLeaveSum += $leaveCount;

                            if ($leaveCount === 1) {
                                $paidWorkday += Carbon::parse((string)$date)->isSaturday() && isset($timesheet['long_leave']) && (in_array($timesheet['long_leave'], [2])) ? 0.5 : $leaveCount;
                                // $paidLeaveSum += $leaveCount;
                            }
                        } else if (!isset($timesheet['is_paid_leave']) && ($isExistPeType || $leaveCount === 1)) {   
                            if (!$matchingOffset && Carbon::parse((string)$date)->isSaturday() && $leaveCount === 1) {
                                $leaveCount = 0.5;
                            }
                            $unPaidLeaveSum += $leaveCount;
                            
                            if (isset($timesheet['is_holiday'])) {
                                $leaveHoliday += $leaveCount;
                            }
                        }

                        // Check if 'extra_workdays' exist and contain the key $date (employee request petition do extra workday)
                        $isExtraWorkday = isset($employee['extra_workdays']);
                        if ($isExtraWorkday && isset($employee['extra_workdays'][$date])) {
                            $extraWorkday += $timesheet['workday'] ?? 0;
                        }

                        // Total time go early
                        $goEarlySum += $timesheet['go_early_total'] ?? 0;
                        // Total time leave early
                        $leaveLateSum += $timesheet['leave_late_total'] ?? 0;
                        // Total do extra warrior time
                        $extraWarriorTime += $timesheet['extra_warrior_time'] ?? 0;

                        // Total paid workday
                        $paidWorkday += round($timesheet['workday'] ?? 0, 2);

                        $workdayHoliday += $matchingOffset ? $matchingOffset->workday : 0;

                    }
                    
                }

                // Create a new array
                $resultArray[] = [
                    'id' => $employee['id'],
                    'fullname' => $employee['fullname'],
                    'date_official' => $employee['date_official'],
                    'go_early_sum' => $goEarlySum,
                    'late_sum' => $lateSum,
                    'late_sum_none_petition' => $lateSumNonePetition,
                    'late_count' => $lateCount,
                    'pe_late_count' => $peLateCount,
                    'early_sum' => $earlySum,
                    'early_count' => $earlyCount,
                    'leave_late_sum' => $leaveLateSum,
                    'pe_early_count' => $peEarlyCount,
                    'total_late_nd_early' => $lateSum + $earlySum,
                    'office_goouts' => $gooutCount,
                    'office_time_goouts' => $gooutSum,
                    'non_office_goouts' => $nonOfficeGooutCount,
                    'non_office_time_goouts' => $nonOfficeGooutSum,
                    'paid_leave' => $paidLeaveSum,
                    'un_paid_leave' => $unPaidLeaveSum,
                    'extra_warrior_time' => $extraWarriorTime,
                    'extra_workday' => $extraWorkday,
                    'origin_workday' => max(0, $paidWorkday - ($paidLeaveSum + $extraWorkday + $workdayHoliday)),
                    'paid_workday' => $paidWorkday - $workdayHoliday,
                    'leave_holiday' => $leaveHoliday,
                ];
            });
            
            return $resultArray;
        } catch (Exception $e) {
            Log::error($e);
        }
    }
}
