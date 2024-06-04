<?php

namespace App\Http\Controllers\api\TrackingDevice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Models\TrackingDevice;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Models\User;
use App\Models\PartnerConfig;
use App\Repositories\HanetRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Tracking Device API
 *
 * @group Tracking Device
 */
class TrackingDeviceController extends Controller
{
    /**
     * @var HanetRepository
     */
    private $hanetRepository;

    public function __construct(HanetRepository $hanetRepository)
    {
        $this->hanetRepository = $hanetRepository;
    }

    /** Tracking Device list
     *
     *
     * @group Tracking Device
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
    public function getDevices()
    {
        try {
            $query = TrackingDevice::query()
                ->select('id', 'name', 'code');

            $devices = $query->get();

            $users = User::select('id', 'fullname', 'user_code')
                        //dont get user_code from employee Tran Thi Quy and Huynh Kieu Xuan Truong
                        ->whereNotIn('id', [1, 69])
                        ->whereNotNull('user_code')
                        ->get();

            $data = [
                'devices' => $devices,
                'users' => $users
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

    public function syncDevices()
    {
        try {
            //Role check
            if (Auth()->user()->permission != "1") {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            //get list tracking devices code
            $trackingDevices = TrackingDevice::select('code')->get();
            $deviceCode = [];
            foreach ($trackingDevices as $device) {
                $deviceCode[] = $device->code;
            }
            
            $partnerConfig = PartnerConfig::where('code', 'HANET')->first();
            
            if (!$partnerConfig) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $setting = json_decode($partnerConfig->setting);
            if (!$setting) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            //start transaction
            DB::beginTransaction();

            if ($setting->access_token) {
                $accessToken = $setting->access_token;

                $data = $this->hanetRepository->getDevices($accessToken);
                
                if ($data->returnCode == 1) {
                    foreach ($data->data as $value) {
                        if (!in_array($value->deviceID, $deviceCode)) {
                            TrackingDevice::create([
                                'name' => $value->deviceName,
                                'code' => $value->deviceID,
                                'type' => 0,
                            ]);
                        }
                    }
                    DB::commit();

                    return response()->json([
                        'success' => __('MSG-S-001'),
                    ], Response::HTTP_OK);
                }
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getDevicesInfo()
    {
        try {
            $type = TrackingDevice::TYPE;

            $devices = TrackingDevice::get();

            $newData = array();
            foreach ($devices as $key => $device) {
                $newData[] = [
                    'id' => $device->id,
                    'name' => $device->name,
                    'code' => $device->code,
                    'type_text' => isset($type[$device->type]) ? $type[$device->type] : ""
                ];
            }

            return response()->json($newData);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
