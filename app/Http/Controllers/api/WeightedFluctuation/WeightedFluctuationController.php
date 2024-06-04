<?php

namespace App\Http\Controllers\api\WeightedFluctuation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\WeightedFluctuation;
use Carbon\Carbon;

/**
 * Weighted Fluctuation API
 *
 * @group Weighted Fluctuation
 */
class WeightedFluctuationController extends Controller
{
    public function list(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $items = WeightedFluctuation::join('users', function ($join) {
                $join->on('users.id', '=', 'weighted_fluctuations.user_id')
                    ->whereNull('users.deleted_at');
            })
            ->join('tasks', function ($join) {
                $join->on('tasks.id', '=', 'weighted_fluctuations.task_id')
                    ->whereNull('tasks.deleted_at');
            })
            ->join('projects', function ($join) {
                $join->on('projects.id', '=', 'weighted_fluctuations.project_id')
                    ->whereNull('projects.deleted_at');
            })
            ->select(
                'weighted_fluctuations.id',
                'users.fullname',
                'users.avatar',
                'tasks.name as task_name',
                'projects.name as project_name',
                'weighted_fluctuations.weight',
                'weighted_fluctuations.issue',
                'weighted_fluctuations.created_at'
            )
            ->orderBy('weighted_fluctuations.created_at', 'desc')->get();

            return response()->json($items);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getLeaderBoard()
    {
        try {
            $departments = config('const.departments');
            //avatar folder path
            $avatarFolder = config('const.avatar_file_folder');

            $result = WeightedFluctuation::join('users', function ($join) {
                $join->on('users.id', '=', 'weighted_fluctuations.user_id')
                    ->whereNull('users.deleted_at');
            })
            ->select(DB::raw("
                users.fullname,
                users.avatar,
                users.department_id,
                coalesce(sum(weighted_fluctuations.weight), 0) as total"))
            ->groupBy('users.id')
            ->orderBy('total', 'desc')
            ->limit(3)
            ->get();

            $leaders = [];
            foreach ($result as $value) {
                $leaders[] = [
                    'fullname' => $value->fullname,
                    'avatar' => '/'.$avatarFolder.'/'.$value->avatar,
                    'department' => $departments[$value->department_id],
                    'total' => $value->total
                ];
            }

            return response()->json($leaders);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
