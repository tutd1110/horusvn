<?php

namespace App\Http\Controllers\api\Holiday;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Models\HolidayOffset;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HolidayOffsetController extends Controller
{
    public function getHolidayOffsets()
    {
        try {
            $offsets = HolidayOffset::select('id', 'holiday_id', 'offset_start_time', 'offset_end_time', 'offset_date', 'workday', 'reason')
                    ->orderBy('offset_date', 'desc')    
                    ->get()
                    ->map(function ($offset) {
                         // Combine offset_date and offset_start_time to create a DateTime value
                        $combinedStartDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $offset['offset_date'] . ' ' . $offset['offset_start_time']);

                        // Combine offset_date and offset_end_time to create a DateTime value
                        $combinedEndDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $offset['offset_date'] . ' ' . $offset['offset_end_time']);

                        // Format the combined DateTime values as "YYYY/MM/DD H:i:s"
                        $offset['offset_start_time'] = $combinedStartDateTime->format('Y/m/d H:i:s');
                        $offset['offset_end_time'] = $combinedEndDateTime->format('Y/m/d H:i:s');

                        return $offset;
                    });

            return response()->json($offsets);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function store(Request $request)
    {
        $requestDatas = $request->all();

        try {
            //start transaction
            DB::beginTransaction();

            //insert holiday offset
            HolidayOffset::create([
                'offset_date' => Carbon::now(),
                'offset_start_time' => Carbon::now()->format('13:30:00'),
                'offset_end_time' => Carbon::now()->format('17:30:00')
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(Request $request)
    {
        $requestDatas = $request->all();

        try {
            $offset = HolidayOffset::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            if (array_key_exists('offset_date', $requestDatas)) {
                $offset->offset_date = $requestDatas['offset_date'];
            }
            if (array_key_exists('offset_start_time', $requestDatas)) {
                $offset->offset_start_time = Carbon::create($requestDatas['offset_start_time'])->format('H:i:s');
            }
            if (array_key_exists('workday', $requestDatas)) {
                $offset->workday = $requestDatas['workday'];
            }
            if (array_key_exists('offset_end_time', $requestDatas)) {
                $offset->offset_end_time = Carbon::create($requestDatas['offset_end_time'])->format('H:i:s');
            }
            if (array_key_exists('holiday_id', $requestDatas)) {
                $offset->holiday_id = $requestDatas['holiday_id'];
            }
            if (array_key_exists('reason', $requestDatas)) {
                $offset->reason = $requestDatas['reason'];
            }

            $offset->save();

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

    public function destroy(Request $request)
    {
        $requestDatas = $request->all();

        try {
            $offset = HolidayOffset::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            //delete holiday
            $offset->delete();

            DB::commit();
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
