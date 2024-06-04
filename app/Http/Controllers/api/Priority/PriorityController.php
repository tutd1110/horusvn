<?php

namespace App\Http\Controllers\api\Priority;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Models\Priority;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Priority API
 *
 * @group Priority
 */
class PriorityController extends Controller
{
    /** Priority list
     *
     *
     * @group Priority
     *
     * @response 200 {
     *  [
     *      {
     *          "id": "1",
     *          "avatar": "iamadmin.png",
     *          "birthday": "2000-01-01",
     *          "check_type": 1,
     *          "date_official": "2022-12-22",
     *          ...
     *      },
     *  ]
     * }
     * @response 404 {
     *    "errors": "Không có dữ liệu được tìm thấy"
     * }
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function getPriorities()
    {
        try {
            $query = Priority::query()
                ->select('id', 'label');

            $priorities = $query->get();

            return response()->json($priorities);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Add new Priority
     *
     * @group Priority
     *
     *
     * @bodyParam label string Tiêu đề
     *
     * @response 403 {
     *    'status' : 403,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 404 {
     *    'status' : 404,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function store(Request $request)
    {
        try {
            $requestDatas = $request->all();

            Priority::performTransaction(function ($model) use ($requestDatas) {
                 //insert priority
                Priority::create([
                    'label' =>  $requestDatas['label'],
                ]);
            });

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Update Priority
     *
     * @group Priority
     *
     * @bodyParam label string Tiêu đề
     *
     * @response 403 {
     *    'status' : 403,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 404 {
     *    'status' : 404,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function update(Request $request)
    {
        $requestDatas = $request->all();

        try {
            $priority = Priority::findOrFail($requestDatas['id']);

            Priority::performTransaction(function ($model) use ($priority, $requestDatas) {
               //insert holiday
                if (array_key_exists('label', $requestDatas)) {
                    $priority->label = $requestDatas['label'];
                }

                $priority->save();
            });

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Delete Priority
     *
     * @group Priority
     *
     * @bodyParam id bigint required
     *
     * @response 404 {
     *    'status' : 404,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 422 {
     *    'status' : 422,
     *    "errors": "Cấp độ công việc không hợp lệ",
     *    "errors_list": {
     *          "id": [
     *              "Cấp độ công việc không hợp lệ"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function destroy(Request $request)
    {
        try {
            $requestDatas = $request->all();
            $priority = Priority::findOrFail($requestDatas['id']);

            Priority::performTransaction(function ($model) use ($priority) {
                //delete Priority
                $priority->delete();
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
}
