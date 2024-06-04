<?php

namespace App\Http\Controllers\api\Employee;

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
use App\Models\User;
use App\Models\Mentee;
use App\Models\UserAltInfo;
use App\Models\UserChildren;
use App\Models\UserJobDetail;
use App\Models\UserAward;
use App\Models\UserActivity;
use App\Models\Task;
use App\Models\TaskTiming;
use App\Models\UserEquipmentHandover;
use Carbon\Carbon;

/**
 * Employee Profile API
 *
 * @group Employee Profile
 */
class EmployeeProfileController extends Controller
{
    public function getMainInfo(Request $request)
    {
        try {
            //list departments
            $departments = config('const.departments');
            //list positions
            $positions = config('const.positions');
            //avatar folder path
            $avatarFolder = config('const.avatar_file_folder');

            //On request
            $requestDatas = $request->all();

            $info = User::join('user_job_details', 'user_job_details.user_id', 'users.id')
                ->join('user_personal_infos', 'user_personal_infos.user_id', 'users.id')
                ->select(
                    'user_personal_infos.fullname as fullname',
                    'users.avatar as avatar',
                    'user_job_details.department_id as department_id',
                    'user_job_details.position as position'
                )
                ->where('users.id', $requestDatas['employee_id'])
                ->first();

            $info->avatar = '/'.$avatarFolder.'/'.$info->avatar;
            $info->position_name = isset($positions[$info->position])
                ? $positions[$info->position] : $info->position;
            $info->department_name = isset($departments[$info->department_id])
                ? $departments[$info->department_id] : $info->department_id;

            return response()->json($info);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getPersonalInfo(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $info = User::join('user_personal_infos', 'user_personal_infos.user_id', 'users.id')
                ->select(
                    'user_personal_infos.fullname as fullname',
                    \DB::raw("to_char(user_personal_infos.birthday, 'DD/MM/YYYY') as date_of_birth"),
                    'users.email as email',
                    'user_personal_infos.phone as phone',
                    'user_personal_infos.gender as gender',
                    'user_personal_infos.id_number as id_number',
                    \DB::raw("to_char(user_personal_infos.date_of_issue, 'DD/MM/YYYY') as date_of_issue"),
                    'user_personal_infos.place_of_issue as place_of_issue',
                    'user_personal_infos.hometown as hometown',
                    'user_personal_infos.current_place as current_place',
                    'user_personal_infos.origin_place as origin_place',
                    'user_personal_infos.note as note',
                )
                ->where('users.id', $requestDatas['employee_id'])
                ->first();

            return response()->json($info);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function updatePersonalInfo(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $employee = User::with('personalInfo')
                ->where('id', $requestDatas['employee_id'])
                ->first();

            //start transaction
            DB::beginTransaction();

            // Update the data in both tables
            $employee->email = $requestDatas['email'];

            $employee->personalInfo->fullname = $requestDatas['fullname'];
            $employee->personalInfo->birthday = $requestDatas['date_of_birth'];
            $employee->birthday = $requestDatas['date_of_birth'];
            $employee->personalInfo->phone = $requestDatas['phone'];
            $employee->personalInfo->gender = $requestDatas['gender'];
            $employee->personalInfo->id_number = $requestDatas['id_number'];
            $employee->personalInfo->date_of_issue = $requestDatas['date_of_issue'];
            $employee->personalInfo->place_of_issue = $requestDatas['place_of_issue'];
            $employee->personalInfo->hometown = $requestDatas['hometown'];
            $employee->personalInfo->current_place = $requestDatas['current_place'];
            $employee->personalInfo->origin_place = $requestDatas['origin_place'];
            $employee->personalInfo->note = $requestDatas['note'];

            $employee->save();
            $employee->personalInfo->save();

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
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

    public function updateAltInfo(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $employee = UserAltInfo::where('user_id', $requestDatas['employee_id'])->first();

            //start transaction
            DB::beginTransaction();

            if ($employee) {
                // Insert data
                $employee->contact_name = $requestDatas['contact_name'];
                $employee->relationship = $requestDatas['relationship'];
                $employee->contact_number = $requestDatas['contact_number'];

                $employee->save();
            } else {
                UserAltInfo::create([
                    'user_id' => $requestDatas['employee_id'],
                    'contact_name' => isset($requestDatas['contact_name']) ? $requestDatas['contact_name'] : null,
                    'relationship' => isset($requestDatas['relationship']) ? $requestDatas['relationship'] : null,
                    'contact_number' => isset($requestDatas['contact_number']) ? $requestDatas['contact_number'] : null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
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

    public function updateJobDetail(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $employee = UserJobDetail::where('user_id', $requestDatas['employee_id'])->first();

            //start transaction
            DB::beginTransaction();
            
            // Insert data
            $employee->start_date = $requestDatas['start_date'];
            $employee->official_start_date = $requestDatas['official_start_date'];
            $employee->termination_date = $requestDatas['termination_date'];
            $employee->disrupted_employment = $requestDatas['disrupted_employment'];

            $employee->save();

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
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

    public function getAltInfo(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $info = UserAltInfo::select(
                'contact_name',
                'relationship',
                'contact_number'
            )
            ->where('user_id', $requestDatas['employee_id'])
            ->first();

            $childs = UserChildren::select(
                'id',
                'fullname',
                'gender',
                'birthday'
            )
            ->where('parent_id', $requestDatas['employee_id'])
            ->get();

            $data = [
                'alt_info' => $info,
                'childrens' => $childs
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

    public function getJobDetails(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $info = UserJobDetail::select(
                \DB::raw("to_char(start_date, 'DD/MM/YYYY') as start_date"),
                \DB::raw("to_char(official_start_date, 'DD/MM/YYYY') as official_start_date"),
                \DB::raw("to_char(termination_date, 'DD/MM/YYYY') as termination_date"),
                'disrupted_employment'
            )
            ->where('user_id', $requestDatas['employee_id'])
            ->first();

            return response()->json($info);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getAwards(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $awards = UserAward::select(
                'id',
                \DB::raw("to_char(start_date, 'DD/MM/YYYY') as start_date"),
                'content'
            )
            ->where('user_id', $requestDatas['employee_id'])
            ->orderBy('start_date', 'desc')
            ->get();

            return response()->json($awards);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getActivities(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $awards = UserActivity::select(
                'id',
                \DB::raw("to_char(start_date, 'DD/MM/YYYY') as start_date"),
                'content'
            )
            ->where('user_id', $requestDatas['employee_id'])
            ->orderBy('start_date', 'desc')
            ->get();

            return response()->json($awards);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function storeAward(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            //start transaction
            DB::beginTransaction();
            
            // Insert data
            UserAward::create([
                'user_id' => $requestDatas['employee_id'],
                'start_date' => $requestDatas['start_date'],
                'content' => $requestDatas['content'],
            ]);

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
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

    public function awardDestroy(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $award = UserAward::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            //delete award
            $award->delete();

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

    public function storeActivity(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            //start transaction
            DB::beginTransaction();
            
            // Insert data
            UserActivity::create([
                'user_id' => $requestDatas['employee_id'],
                'start_date' => $requestDatas['start_date'],
                'content' => $requestDatas['content'],
            ]);

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
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

    public function activityDestroy(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $activity = UserActivity::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            //delete activity
            $activity->delete();

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

    public function getProjects(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();
            $employeeId = $requestDatas['employee_id'];

            $employee = User::findOrFail($employeeId);

            $subquery = TaskTiming::select(
                'task_timings.task_id',
                DB::raw("min(task_timings.work_date) as start_date"),
                DB::raw("max(task_timings.work_date) as end_date")
            )
            ->join('tasks', 'task_timings.task_id', '=', 'tasks.id')
            ->where('tasks.user_id', $employee->id)
            ->whereNull('task_timings.deleted_at')
            ->whereNull('tasks.deleted_at')
            ->groupBy('task_timings.task_id');

            $projects = Task::join(
                \DB::raw("(select distinct * from task_projects where deleted_at is null) as tp"),
                function ($join) {
                    $join->on('tp.task_id', '=', 'tasks.id');
                }
            )
            ->leftJoinSub($subquery, 'task_timings', function ($join) {
                $join->on('task_timings.task_id', '=', 'tasks.id');
            })
            ->join('projects', function ($join) {
                $join->on('projects.id', '=', 'tp.project_id')->whereNull('projects.deleted_at');
            })
            ->select(\DB::raw("projects.id as id,
                projects.name as name,
                to_char(min(task_timings.start_date), 'DD/MM/YYYY') as start_date,
                to_char(max(task_timings.end_date), 'DD/MM/YYYY') as end_date,
                sum(case when tasks.user_id = ? then tp.weight else 0 end) as project_weight,
                sum(case when tasks.department_id = ? then tp.weight else 0 end) as department_weight"))
            ->where(function ($query) use ($employee) {
                $query->where('tasks.user_id', $employee->id)
                    ->orWhere('tasks.department_id', $employee->department_id);
            })
            ->groupBy('projects.id')
            ->setBindings([$employee->id, $employee->department_id], 'select')
            ->get();

            // loop through each project and calculate the duration in days
            $projects->transform(function ($project) {
                if ($project->start_date && $project->end_date) {
                    $startDate = Carbon::createFromFormat('d/m/Y', $project->start_date);
                    $endDate = Carbon::createFromFormat('d/m/Y', $project->end_date);
                    $project->duration = $endDate->diffInDays($startDate);

                    $project->project_weight = round($project->project_weight ?? 0, 2);
                    $project->department_weight = round($project->department_weight ?? 0, 2);
                    $project->warrior = null;
                } else {
                    return null;
                }

                return $project;
            });

            $projects = $projects->filter(function ($project) {
                return $project !== null;
            })->values()->toArray();

            return response()->json($projects);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getMentees(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $mentees = Mentee::join('users', 'users.id', '=', 'mentees.mentee_id')
            ->select('mentees.id', 'users.fullname as mentee_name')
            ->where('mentees.mentor_id', $requestDatas['employee_id'])
            ->get();

            return response()->json($mentees);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function storeMentee(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            //start transaction
            DB::beginTransaction();
            
            // Insert data
            Mentee::create([
                'mentor_id' => $requestDatas['employee_id'],
                'mentee_id' => $requestDatas['mentee_id']
            ]);

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
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

    public function menteeDestroy(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $mentee = Mentee::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            //delete activity
            $mentee->delete();

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

    public function getEquipmentHandovers(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $equipments = UserEquipmentHandover::select(
                'id',
                \DB::raw("to_char(handover_date, 'DD/MM/YYYY') as handover_date"),
                'name',
                'detail',
                'status'
            )
            ->where('user_id', $requestDatas['employee_id'])
            ->orderBy('handover_date', 'desc')
            ->get();

            return response()->json($equipments);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function storeEquipmentHandover(Request $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            //start transaction
            DB::beginTransaction();
            
            // Insert data
            UserEquipmentHandover::create([
                'user_id' => $requestDatas['employee_id'],
                'handover_date' => $requestDatas['handover_date'],
                'name' => $requestDatas['name'],
                'detail' => $requestDatas['detail'],
                'status' => $requestDatas['status'],
            ]);

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
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

    public function equipmentHandoverDestroy(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $equipment = UserEquipmentHandover::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            //delete activity
            $equipment->delete();

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

    public function storeChildren(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            //start transaction
            DB::beginTransaction();

            // Insert data
            UserChildren::create([
                'parent_id' => $requestDatas['employee_id'],
                'birthday' => $requestDatas['birthday'],
                'fullname' => $requestDatas['fullname'],
                'gender' => $requestDatas['gender']
            ]);

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function destroyChildren(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $children = UserChildren::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            //delete activity
            $children->delete();

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }
}
