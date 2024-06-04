<?php

namespace App\Http\Controllers\api\Sticker;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api\CommonController;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Models\Sticker;
use App\Models\Priority;
use App\Models\Task;
use App\Models\TaskTiming;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\api\Sticker\StickerRegisterRequest;
use App\Http\Requests\api\Sticker\StickerEditRequest;
use Carbon\Carbon;

/**
 * Sticker API
 *
 * @group Sticker
 */
class StickerController extends Controller
{
    /** Sticker list
     *
     *
     * @group Sticker
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
    public function getStickers(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $query = Sticker::query()
                ->select(
                    'id',
                    'ordinal_number',
                    'name',
                    'department_id',
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
                ->where(function ($query) use ($requestDatas) {
                    if (isset($requestDatas['department_id']) && !empty($requestDatas['department_id'])) {
                        $query->where('department_id', $requestDatas['department_id']);
                    }
                });

            $stickers = $query->orderBy('ordinal_number', 'asc')->get();

            return response()->json($stickers);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getDepartments()
    {
        try {
            //list users by department with job
            $departments = CommonController::getDepartmentsJob();

            return response()->json($departments);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Add new Sticker
     *
     * @group Sticker
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
    public function store(StickerRegisterRequest $request)
    {
        try {
            $requestDatas = $request->all();

            Sticker::performTransaction(function ($model) use ($requestDatas) {
                //insert sticker
                Sticker::create([
                    'name' => $requestDatas['name'],
                    'department_id' => $requestDatas['department_id'],
                    'level_1' => $requestDatas['level_1'] ?? null,
                    'level_2' => $requestDatas['level_2'] ?? null,
                    'level_3' => $requestDatas['level_3'] ?? null,
                    'level_4' => $requestDatas['level_4'] ?? null,
                    'level_5' => $requestDatas['level_5'] ?? null,
                    'level_6' => $requestDatas['level_6'] ?? null,
                    'level_7' => $requestDatas['level_7'] ?? null,
                    'level_8' => $requestDatas['level_8'] ?? null,
                    'level_9' => $requestDatas['level_9'] ?? null,
                    'level_10' => $requestDatas['level_10'] ?? null
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

    /** Update Sticker
     *
     * @group Sticker
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
    public function update(StickerEditRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();
            $sticker = Sticker::findOrFail($requestDatas['id']);

            //insert sticker
            $sticker->level_1 = $requestDatas['level_1'];
            if ($sticker->isDirty('level_1')) {
                CommonController::updateTaskWeight($sticker->id, 1, $sticker->level_1);
            }

            $sticker->level_2 = $requestDatas['level_2'];
            if ($sticker->isDirty('level_2')) {
                CommonController::updateTaskWeight($sticker->id, 2, $sticker->level_2);
            }

            $sticker->level_3 = $requestDatas['level_3'];
            if ($sticker->isDirty('level_3')) {
                CommonController::updateTaskWeight($sticker->id, 3, $sticker->level_3);
            }

            $sticker->level_4 = $requestDatas['level_4'];
            if ($sticker->isDirty('level_4')) {
                CommonController::updateTaskWeight($sticker->id, 4, $sticker->level_4);
            }

            $sticker->level_5 = $requestDatas['level_5'];
            if ($sticker->isDirty('level_5')) {
                CommonController::updateTaskWeight($sticker->id, 5, $sticker->level_5);
            }

            $sticker->level_6 = $requestDatas['level_6'];
            if ($sticker->isDirty('level_6')) {
                CommonController::updateTaskWeight($sticker->id, 6, $sticker->level_6);
            }

            $sticker->level_7 = $requestDatas['level_7'];
            if ($sticker->isDirty('level_7')) {
                CommonController::updateTaskWeight($sticker->id, 7, $sticker->level_7);
            }

            $sticker->level_8 = $requestDatas['level_8'];
            if ($sticker->isDirty('level_8')) {
                CommonController::updateTaskWeight($sticker->id, 8, $sticker->level_8);
            }

            $sticker->level_9 = $requestDatas['level_9'];
            if ($sticker->isDirty('level_9')) {
                CommonController::updateTaskWeight($sticker->id, 9, $sticker->level_9);
            }

            $sticker->level_10 = $requestDatas['level_10'];
            if ($sticker->isDirty('level_10')) {
                CommonController::updateTaskWeight($sticker->id, 10, $sticker->level_10);
            }

            Sticker::performTransaction(function ($model) use ($sticker) {
                //insert sticker
                $sticker->save();
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

    /** Quick Update Sticker
     *
     * @group Sticker
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
    public function quickUpdate(StickerEditRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();
            $sticker = Sticker::findOrFail($requestDatas['id']);

            //insert sticker
            if (array_key_exists('ordinal_number', $requestDatas)) {
                $sticker->ordinal_number = $requestDatas['ordinal_number'];
            }
            if (array_key_exists('name', $requestDatas)) {
                $sticker->name = $requestDatas['name'];
            }
            if (array_key_exists('department_id', $requestDatas)) {
                $sticker->department_id = $requestDatas['department_id'];
            }

            Sticker::performTransaction(function ($model) use ($sticker) {
                //insert sticker
                $sticker->save();
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

    /** Delete Sticker
     *
     * @group Sticker
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
     *    "errors": "Loại công việc không hợp lệ",
     *    "errors_list": {
     *          "id": [
     *              "Loại công việc không hợp lệ"
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
        $requestDatas = $request->all();

        try {
            $sticker = Sticker::findOrFail($requestDatas['id']);
            $id = $sticker->id;

            Sticker::performTransaction(function ($model) use ($sticker, $id) {
                if ($sticker->delete()) {
                    CommonController::clearTaskWeight($id);
                }
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
