<?php

namespace App\Http\Controllers\api\Holiday;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Models\Holiday;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\api\Holiday\HolidayRegisterRequest;
use App\Http\Requests\api\Holiday\HolidayEditRequest;
use App\Http\Requests\api\Holiday\HolidayDeleteRequest;
use Carbon\Carbon;

/**
 * Holiday API
 *
 * @group Holiday
 */
class HolidayController extends Controller
{
    /** Holiday list
     *
     *
     * @group Holiday
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
    public function getHolidays()
    {
        try {
            $query = Holiday::query()
                ->select('id', 'name', 'start_date', 'end_date');

            $holidays = $query->get();

            return response()->json($holidays);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Add new Holiday
     *
     * @group Holiday
     *
     *
     * @bodyParam name string Tiêu đề
     * @bodyParam start_date date Ngày bắt đầu
     * @bodyParam end_date date Ngày kết thúc
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
     * @response 422 {
     *      "status": 422,
     *      "errors": "Ngày bắt đầu không đúng định dạng",
     *      "errors_list": {
     *          "start_date": [
     *              "Ngày bắt đầu không đúng định dạng"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function store(HolidayRegisterRequest $request)
    {
        $requestDatas = $request->all();

        try {
            //Role check
            if (Auth()->user()->permission != "1") {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            //start transaction
            DB::beginTransaction();

            //insert holiday
            Holiday::create([
                'name' =>  $requestDatas['name'],
                'start_date' => Carbon::create($requestDatas['start_date'])->format('Y/m/d 00:00:00'),
                'end_date' => Carbon::create($requestDatas['end_date'])->format('Y/m/d 23:59:59')
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Update Holiday
     *
     * @group Holiday
     *
     * @bodyParam name string Tiêu đề
     * @bodyParam start_date date Ngày bắt đầu
     * @bodyParam end_date date Ngày kết thúc
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
     * @response 422 {
     *      "status": 422,
     *      "errors": "Ngày bắt đầu không đúng định dạng",
     *      "errors_list": {
     *          "start_date": [
     *              "Ngày bắt đầu không đúng định dạng"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function update(HolidayEditRequest $request)
    {
        $requestDatas = $request->all();

        try {
            //Role check
            if (Auth()->user()->permission != "1") {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            $holiday = Holiday::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            //insert holiday
            if (array_key_exists('name', $requestDatas)) {
                $holiday->name = $requestDatas['name'];
            }
            if (array_key_exists('start_date', $requestDatas)) {
                $holiday->start_date = Carbon::create($requestDatas['start_date'])->format('Y/m/d 00:00:00');
            }
            if (array_key_exists('end_date', $requestDatas)) {
                $holiday->end_date = Carbon::create($requestDatas['end_date'])->format('Y/m/d 23:59:59');
            }

            $holiday->save();

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Delete Holiday
     *
     * @group Holiday
     *
     * @bodyParam id bigint required Mã ngày nghỉ
     *
     * @response 404 {
     *    'status' : 404,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 422 {
     *    'status' : 422,
     *    "errors": "Ngày nghỉ không hợp lệ",
     *    "errors_list": {
     *          "id": [
     *              "Ngày nghỉ không hợp lệ"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function destroy(HolidayDeleteRequest $request)
    {
        $requestDatas = $request->all();

        try {
            $holiday = Holiday::findOrFail($requestDatas['id']);

            //Role check
            if (Auth()->user()->permission != 1) {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            //start transaction
            DB::beginTransaction();

            //delete holiday
            $holiday->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
