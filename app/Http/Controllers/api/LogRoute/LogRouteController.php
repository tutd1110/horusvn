<?php

namespace App\Http\Controllers\api\LogRoute;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\LogRoute;
use App\Models\User;
use Carbon\Carbon;

/**
 * Log Route API
 *
 * @group Log Route
 */
class LogRouteController extends Controller
{
    public function getSelboxes()
    {
        try {
            //userSelbox
            $users = User::select('id', 'fullname')->get();

            return response()->json($users);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getLogRoutes(Request $request)
    {
        try {
            //Role check
            if (Auth()->user()->id != 107 && Auth()->user()->id != 161 && Auth()->user()->id != 63) {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            $query = LogRoute::leftJoin('users', 'users.id', '=', 'log_routes.user_id')
            ->select(
                'log_routes.id as id',
                'users.fullname as fullname',
                'log_routes.uri as uri',
                'log_routes.request_body as request_body',
                'log_routes.response as response',
                'log_routes.created_at as created_at'
            )
            ->orderBy('created_at', 'desc');

            //Add SQL according to requested search conditions
            //On request
            $requestDatas = $request->all();
            if (!empty($requestDatas)) {
                //get json from request
                $query = $this->addSqlWithSorting($requestDatas, $query);
            }

            $total = $query->get()->count();

            $logs = $query->offset(($requestDatas['current_page'] - 1) * 20)
                ->limit(20)
                ->get();

            //no search results
            if (count($logs) === 0) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $data = [
                'items' => $logs,
                'totalItems' => $total
            ];

            return response()->json($data);
        } catch (Exception $e) {
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
        if (!empty($requestDatas['start_time'])) {
            $addedQuery = $addedQuery->where(
                'log_routes.created_at',
                '>=',
                Carbon::create($requestDatas['start_time'])->format('Y/m/d 00:00:00')
            );
        }
        if (!empty($requestDatas['end_time'])) {
            $addedQuery = $addedQuery->where(
                'log_routes.created_at',
                '<=',
                Carbon::create($requestDatas['end_time'])->format('Y/m/d 23:59:59')
            );
        }
        if (!empty($requestDatas['user_id'])) {
            $addedQuery = $addedQuery->where('log_routes.user_id', $requestDatas['user_id']);
        }

        if (!empty($requestDatas['uri'])) {
            $addedQuery = $addedQuery->where(
                DB::raw('lower(log_routes.uri)'),
                'LIKE',
                '%'.mb_strtolower(urldecode($requestDatas['uri']), 'UTF-8').'%'
            );
        }
        if (!empty($requestDatas['request_body'])) {
            $addedQuery = $addedQuery->where(
                DB::raw('lower(log_routes.request_body)'),
                'LIKE',
                '%'.mb_strtolower(urldecode($requestDatas['request_body']), 'UTF-8').'%'
            );
        }

        return $addedQuery;
    }

    public function getLogReview(Request $request)
    {
        try {
            //Role check
            if (Auth()->user()->id != 51 && Auth()->user()->id != 161) {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            $query = LogRoute::leftJoin('users as log_users', 'log_users.id', '=', 'log_routes.user_id')
            ->leftJoin('reviews', function ($join) {
                $join->on('reviews.id', '=', DB::raw("CAST(jsonb_extract_path_text(log_routes.request_body::jsonb, 'id') AS bigint)"));
            })
            ->join('users as review_users', function ($join) {
                $join->on('review_users.id', '=', 'reviews.employee_id');
            })
            ->select(
                'log_routes.id as id',
                'log_users.fullname as fullname',
                'log_routes.uri as uri',
                'log_routes.request_body as request_body',
                'log_routes.response as response',
                'log_routes.created_at as created_at',
                'reviews.period as period',
                'reviews.employee_id as employee_id',
                'review_users.fullname as review_user',
                DB::raw("jsonb_extract_path_text(log_routes.request_body::jsonb, 'id') as id_review")
            )
            ->orderBy('created_at', 'desc');



            //Add SQL according to requested search conditions
            //On request
            $requestDatas = $request->all();
            if (!empty($requestDatas)) {
                //get json from request
                $query = $this->addSqlWithSorting($requestDatas, $query);
            }

            
            $total = $query->get()->count();

            $logs = $query->offset(($requestDatas['current_page'] - 1) * 20)
                ->limit(20)
                ->get();
            $reviewPeriod = config('const.review_period');
            foreach ($logs as &$log) {
                $periodIndex = $log['period'];
                $log['period_name'] = isset($reviewPeriod[$periodIndex]) ? $reviewPeriod[$periodIndex] : 'Unknown';
            }

            // $logs->transform(function ($item) {
            //     // Parse request_body thành một mảng asscociative
            //     $requestBody = json_decode($item->request_body, true);
            //     // Tạo một mảng mới từ dữ liệu hiện có và thêm trường id_review vào mảng
            //     return array_merge($item->toArray(), ['id_review' => $requestBody['id']]);
            // });

            //no search results
            if (count($logs) === 0) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $data = [
                'items' => $logs,
                'totalItems' => $total
            ];

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
