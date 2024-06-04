<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Petition;
use App\Models\DeadlineModification;
use App\Models\TaskAssignment;
use App\Models\Review;
use App\Models\Mentee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Who Am I API
 *
 * @group WhoAmI
 */
final class WhoAmIController extends Controller
{
    
    /**
     * Return the information of the logged-in user
     *
     * @group WhoAmI
     *
     * @response 200 {
     *    key : value,
     *    key : value,
     *      .. see table design
     * }
     */
    public function __invoke(Request $request)
    {
        try {
            //employees role
            $pmIdsRole = config('const.employee_id_pm_roles');
            $addPermission = config('const.employee_add_permission');

            // Select specific columns for the authenticated user
            $user = User::select('id', 'avatar', 'fullname', 'email', 'position', 'department_id')->find(Auth()->user()->id);

            $is_authority = false;
            $add_permission = false;
            if (in_array($user->id, $pmIdsRole)) {
                $is_authority = true;
            }
            if (in_array($user->id, $addPermission)) {
                $add_permission = true;
            }

            $petitionCount = 0;
            $deadlineModCount = 0;
            $reviewCount = 0;

            if ($user->position > 1 || $is_authority || $user->id === 82) {
                //get all petitions
                $petitions = Petition::whereNull('status');
                if (Auth()->user()->id === 51) {
                    $petitions->where('petitions.approve_pm', true);
                }
                $petitions = $petitions->get();
                $petitionCount = $petitions->count();
            }

            //deadline modification
            if (in_array($user->id, $pmIdsRole)) {
                $deadlineModCount = DeadlineModification::join('users', function ($join) {
                    $join->on('users.id', '=', 'deadline_modifications.user_id')
                        ->whereNull('users.deleted_at');
                })
                ->join('tasks', function ($join) {
                    $join->on('tasks.id', '=', 'deadline_modifications.task_id')
                        ->whereNull('tasks.deleted_at');
                })
                ->where('deadline_modifications.status', 0)
                ->when($user->position == 1, function ($query) use ($user) {
                    $query->where('users.department_id', $user->department_id);
                })
                ->when($user->position == 0, function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                })
                ->count('deadline_modifications.id');
            }
            
            // if (in_array($user->department_id, [3,5])) {
            //     $bugs = TaskAssignment::query()
            //             ->select(DB::raw("
            //                 count(id) filter (where status = 0 or status = 1) as total_department_bugs,
            //                 count(id) filter (where (status = 0 or status = 1) and
            //                     tester_id = ".$user->id.") as total_my_bugs"))
            //             ->get();
            // } else {
            //     $bugs = TaskAssignment::query()
            //             ->select(DB::raw("
            //                 count(id) filter (where (status = 0 or status = 1 or status = 4) and
            //                     assigned_department_id = ".$user->department_id.") as total_department_bugs,
            //                 count(id) filter (where (status = 0 or status = 1 or status = 4) and
            //                     assigned_user_id = ".$user->id.") as total_my_bugs"))
            //             ->get();
            // }

            $bugs = TaskAssignment::query()
                ->select(DB::raw("
                    count(id) filter (where status = 0 or status = 1) as created_total_department_bugs,
                    count(id) filter (where (status = 0 or status = 1) and
                        tester_id = ".$user->id.") as created_total_my_bugs,
                    count(id) filter (where (status = 0 or status = 1 or status = 4) and
                        assigned_department_id = ".$user->department_id.") as assigned_total_department_bugs,
                    count(id) filter (where (status = 0 or status = 1 or status = 4) and
                        assigned_user_id = ".$user->id.") as assigned_total_my_bugs",
                        ))
                ->get();

            $menteeIds = [];
            switch ($user->position) {
                case 1:
                    $progress = 1;
                    break;

                case 2:
                    $progress = 2;
                    break;

                case 3:
                    $progress = 3;
                    break;
                
                default:
                    $progress = 0;
                    $menteeIds = Mentee::where('mentor_id', $user->id)->pluck('mentee_id')->toArray();

                    break;
            }

            $reviewCount = Review::join('users', function ($join) {
                $join->on('users.id', '=', 'reviews.employee_id');
            })
            ->where('reviews.progress', $progress)
            ->where(function ($query) use ($user, $progress) {
                if ($progress == 0) {
                    $query->where('users.id', $user->id);
                } elseif ($progress == 1) {
                    $query->where('users.department_id', $user->department_id);
                }
            })
            ->orWhere(function ($query) use ($user, $menteeIds) {
                if (count($menteeIds) > 0) {
                    $query->where('users.department_id', $user->department_id)
                        ->where('reviews.progress', 0.5)
                        ->whereIn('users.id', $menteeIds);
                }
            })
            ->count();

            $data = [
                'user' => $user,
                'is_mentor' => count($menteeIds) > 0 ? true : false,
                'petition_count' => $petitionCount,
                'deadline_mod_count' => $deadlineModCount,
                'bugs' => $bugs[0],
                'review_count' => $reviewCount,
                'is_authority' => $is_authority,
                'add_permission' => $add_permission
            ];

            return $data;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                                        'errors' => $e->getMessage(),], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
