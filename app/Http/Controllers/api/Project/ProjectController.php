<?php

namespace App\Http\Controllers\api\Project;

use App\Http\Controllers\Controller;
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
use App\Models\Task;
use App\Models\User;
use App\Http\Requests\api\Project\GetProjectRequest;
use App\Http\Requests\api\Project\GetUserReportRequest;
use App\Http\Requests\api\Project\ProjectRegisterRequest;
use App\Http\Requests\api\Project\GetProjectByIdRequest;
use App\Http\Requests\api\Project\ProjectEditRequest;
use App\Http\Requests\api\Project\ProjectDeleteRequest;
use App\Http\Requests\api\Project\ProjectQuickEditRequest;
use Carbon\Carbon;
use File;
use Storage;

/**
 * Project API
 *
 * @group Project
 */
class ProjectController extends Controller
{
    /** Project
     *
     *
     * @group Project
     *
     * @bodyParam start_date date optional Ngày bắt đầu
     * @bodyParam end_date date optional Ngày kết thúc
     * @bodyParam user_ids[] array optional Người tham gia
     * @bodyParam name string optional Tên dự án
     * @bodyParam code string optional Mã dự án
     *
     * @response 200 {
     *  [
     *      {
     *          "id": 1,
     *          "name": "WMZ 1.0",
     *          "code": "W",
     *          "start_date": "2022/12/01 12:12:12",
     *          "end_date": "2022/12/31 12:12:12",
     *          "updated_at": "2022/12/31 12:12:12",
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
    public function getProject(GetProjectRequest $request)
    {
        try {
            $query = Project::leftJoin('task_projects', function ($join) {
                $join->on('task_projects.project_id', '=', 'projects.id')->whereNull('task_projects.deleted_at');
            })
            ->leftJoin('task_timings', function ($join) {
                $join->on('task_timings.task_id', '=', 'task_projects.task_id')->whereNull('task_timings.deleted_at');
            })
            ->select(DB::raw('projects.id as id,
                                projects.ordinal_number as ordinal_number,
                                projects.name as name,
                                projects.code as code,
                                min(task_timings.work_date) as start_date,
                                max(task_timings.work_date) as end_date,
                                change_weight,
                                projects.note,
                                projects.updated_at as updated_at'));

            //Add SQL according to requested search conditions
            //On request
            $requestDatas = $request->all();
            if (!empty($requestDatas)) {
                //get json from request
                $query = $this->addSqlWithSorting($requestDatas, $query);
            }
            $projects = $query->distinct()->groupBy('projects.id')->get()->toArray();

            //no search results
            if (count($projects) === 0) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $order = array_column($projects, 'ordinal_number');

            array_multisort($order, SORT_ASC, $projects);

            return response()->json($projects);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Select boxes data
     *
     *
     * @group Project
     *
     *
     * @response 200 {
     *   [
     *      "users" : [
     *          {
     *              "id: 1,
     *              "fullname" : "iamadmin,
     *          },
     *          ...
     *      ],
     *      "date_period" : {
     *          "date_start": "2022/12/12",
     *          "date_end": "2022/12/12"
     *      }
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
            $projects = Project::select('id', 'name', 'code')->orderBy('ordinal_number', 'asc')->get();
            
            $users = User::withFilterByGroup()
                    ->select('id', 'fullname')
                    ->where('position', '!=', 3)
                    ->where('user_status', '!=', 2)
                    ->get();

            //period time
            $datePeriod = [
                'date_start' => Carbon::now()->startOfMonth()->format("Y/m/d"),
                'date_end' => Carbon::now()->endOfMonth()->format('Y/m/d')
            ];

            $listBoxed = [
                'users' => $users,
                'projects' => $projects,
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

    /** Get users select box
     *
     *
     * @group Project
     *
     *
     * @response 200 {
     *   [
     *      {
     *          "id: 1,
     *          "fullname" : "iamadmin,
     *      },
     *      ...
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function getUsers()
    {
        try {
            $query = User::withFilterByGroup()
                ->select(DB::raw('users.id as id,
                                    users.fullname as fullname'))
                ->where('users.user_status', '!=', 2);

            $users = $query->get();

            return response()->json($users);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Project
     *
     * Get project by id
     *
     * @group Project
     *
     * @bodyParam id bigint required (project id)
     *
     * @response 200 {
     *  [
     *      {
     *          "id": "1",
     *          "name": "World War 2.0",
     *          "code: "WW20",
     *          "description": "<ol><li>Nothing</li><li>Else</li></ol>",
     *          "user_ids": [
     *              "user_id": 1
     *          ]
     *      },
     *  ]
     * }
     *
     * @response 404 {
     *    "status": 404,
     *    "errors": "Dự án không tồn tại"
     * }
     *
     * @response 422 {
     *      "status": 422,
     *      "errors": "Dự án không tồn tại",
     *      "errors_list": {
     *          "id": [
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
    public function getProjectById(GetProjectByIdRequest $request)
    {
        try {
            $project = Project::where('id', $request->id)
                ->first([
                    'projects.id',
                    'projects.name',
                    'projects.code',
                    'projects.description',
                    'projects.updated_at',
                    'projects.note',
                ]);

            $userIds = ProjectUser::where("project_users.project_id", $project->id)->get(["user_id"])->toArray();

            $arr = [];
            foreach ($userIds as $value) {
                $arr[] = $value['user_id'];
            }
            $project->user_ids = $arr;

            return response()->json($project);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
        }
    }

    /** Project Store
     *
     * @group Project
     *
     *
     * @bodyParam name string required Tên dự án
     * @bodyParam code string required Mã dự án
     * @bodyParam user_ids array required Người tham gia dự án
     * @bodyParam description longText required Mô tả dự án
     *
     * @response 404 {
     *    'status' : 404,
     *    'errors' : 'XXXXXXXX'
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
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function store(ProjectRegisterRequest $request)
    {
        try {
            $requestDatas = $request->all();

            Project::performTransaction(function ($model) use ($requestDatas) {
                $project = Project::create([
                    'name' =>  $requestDatas['name'],
                    'code' =>  $requestDatas['code'],
                    'description' => isset($requestDatas['description']) ? $requestDatas['description'] : null,
                ]);
    
                //insert project users
                foreach ($requestDatas['user_ids'] as $item) {
                    ProjectUser::create([
                        'project_id' =>  $project->id,
                        'user_id' => $item['id'],
                    ]);
                }
            });
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Update Project
     *
     * @group Project
     *
     * @bodyParam id bigint required ID Dự án
     * @bodyParam name string required Tên dự án
     * @bodyParam code string required Mã dự án
     * @bodyParam user_ids array required Người tham gia dự án
     * @bodyParam description longText required Mô tả dự án
     * @bodyParam check_updated_at date required
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
     *    "status": 422,
     *    "errors": "Ngày kết thúc không được nhỏ hơn Ngày bắt đầu",
     *    "errors_list": {
     *          "end_date": [
     *             "Ngày kết thúc không được nhỏ hơn Ngày bắt đầu"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function update(ProjectEditRequest $request)
    {
        try {
            $requestDatas = $request->all();

            $project = Project::findOrFail($requestDatas['id']);
            //exclusion control
            $project->setCheckUpdatedAt($requestDatas['check_updated_at']);

            Project::performTransaction(function ($model) use ($project, $requestDatas) {
                //insert project
                $project->name = $requestDatas['name'];
                $project->code = $requestDatas['code'];
                $project->note = isset($requestDatas['note']) ? $requestDatas['note'] : null ;
                $project->description = isset($requestDatas['description']) ? $requestDatas['description'] : null;

                $project->save();

                //delete all rows from project_users table with project_id before insert new
                ProjectUser::where("project_users.project_id", $project->id)->forceDelete();
        
                //then insert again
                foreach ($requestDatas['user_ids'] as $item) {
                    ProjectUser::create([
                        'project_id' =>  $project->id,
                        'user_id' => $item['id'],
                    ]);
                }
            });
        } catch (ExclusiveLockException $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function quickUpdate(ProjectQuickEditRequest $request)
    {
        try {
            $requestDatas = $request->all();

            Project::performTransaction(function ($model) use ($requestDatas) {
                $project = Project::findOrFail($requestDatas['id']);

                if (array_key_exists('ordinal_number', $requestDatas)) {
                    $project->ordinal_number = $requestDatas['ordinal_number'];
                }
                if (array_key_exists('change_weight', $requestDatas)) {
                    $project->change_weight = $requestDatas['change_weight'];
                }
                if (array_key_exists('project_parent_time', $requestDatas)) {
                    $project->project_parent_time = $requestDatas['project_parent_time'];
                }
                if (array_key_exists('note', $requestDatas)) {
                    $project->note = $requestDatas['note'];
                }

                //insert task
                $project->save();
            });

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Delete project
     *
     * @group Project
     *
     * @bodyParam id bigint required Id dự án
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
    public function destroy(ProjectDeleteRequest $request)
    {
        try {
            $requestDatas = $request->all();
            $project = Project::findOrFail($requestDatas['id']);
            $id = $project->id;
            //exclusion control
            $project->setCheckUpdatedAt($requestDatas['check_updated_at']);

            Project::performTransaction(function ($model) use ($project, $id) {
                //delete project
                if ($project->delete()) {
                    //delete project users
                    ProjectUser::where("project_users.project_id", $id)->delete();
                }
            });
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

        //Change the SQL according to the requested search conditions
        if (!empty($requestDatas['user_ids'])) {
            $addedQuery = $addedQuery->leftJoin('project_users', 'project_users.project_id', '=', 'projects.id');
            $addedQuery = $addedQuery->whereIn('project_users.user_id', $requestDatas['user_ids']);
        }

        if (!empty($requestDatas['start_date'])) {
            $addedQuery = $addedQuery->whereDate(
                'task_timings.work_date',
                '>=',
                Carbon::create($requestDatas['start_date'])->format('Y/m/d 00:00:00')
            );
        }

        if (!empty($requestDatas['end_date'])) {
            $addedQuery = $addedQuery->whereDate(
                'task_timings.work_date',
                '<=',
                Carbon::create($requestDatas['end_date'])->format('Y/m/d 23:59:59')
            );
        }

        if (!empty($requestDatas['name'])) {
            $addedQuery = $addedQuery->where(
                DB::raw('lower(projects.name)'),
                'LIKE',
                '%'.mb_strtolower($requestDatas['name'], 'UTF-8').'%'
            );
        }

        if (!empty($requestDatas['code'])) {
            $addedQuery = $addedQuery->where(
                DB::raw('lower(projects.code)'),
                'LIKE',
                '%'.mb_strtolower($requestDatas['code'], 'UTF-8').'%'
            );
        }

        return $addedQuery;
    }
}
