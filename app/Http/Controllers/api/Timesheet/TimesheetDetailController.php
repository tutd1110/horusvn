<?php

namespace App\Http\Controllers\api\Timesheet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\TimesheetDetail;
use App\Models\User;
use App\Models\PartnerConfig;
use App\Models\Petition;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Repositories\HanetRepository;
use App\Http\Requests\api\Timesheet\TimesheetDetail\GetTimesheetDetailRequest;
use App\Http\Requests\api\Timesheet\TimesheetDetail\SyncTimesheetDetailRequest;
use Carbon\Carbon;

/**
 * Timesheet Detail API
 *
 * @group Timesheet Detail
 */
class TimesheetDetailController extends Controller
{
    /**
     * @var HanetRepository
     */
    private $hanetRepository;

    public function __construct(HanetRepository $hanetRepository)
    {
        $this->hanetRepository = $hanetRepository;
    }

    const DEVICE_CHECKIN_ID = 'C21281M767';

    /** Timesheet Detail list
     *
     *
     * @group Timesheet Detail
     *
     * @bodyParam user_id integer optional (Employee's ID)
     * @bodyParam date date required (Ngày chấm công)
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
     * @response 422 {
     *      "status": 422,
     *      "errors": "Trạng thái nhân viên không được để trống",
     *      "errors_list": {
     *          "user_status": [
     *              "Trạng thái nhân viên không được để trống"
     *          ]
     *      }
     * }
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function getTimesheetDetail(GetTimesheetDetailRequest $request)
    {
        try {
            $query = TimesheetDetail::query()
                ->leftJoin('users', 'users.user_code', '=', 'timesheet_details.user_code')
                ->leftJoin('tracking_devices', 'tracking_devices.code', '=', 'timesheet_details.device_id')
                ->select(DB::raw('timesheet_details.id as id,
                                users.fullname as fullname,
                                timesheet_details.time as time,
                                timesheet_details.detected_image_url as detected_image_url,
                                timesheet_details.person_title as person_title,
                                tracking_devices.name as device_name'))
                ->orderBy('time', 'asc');

            //Add SQL according to requested search conditions
            //On request
            $requestDatas = $request->all();

            $query = $this->addSqlWithSorting($requestDatas, $query);

            //db query
            $total = $query->get()->count();
            if ($total/$requestDatas['per_page'] < $requestDatas['current_page']) {
                $requestDatas['current_page'] = ceil($total/$requestDatas['per_page']);
            }
            $timesheets = $query->offset(($requestDatas['current_page'] - 1) * $requestDatas['per_page'])
            ->limit($requestDatas['per_page'])
            ->get();

            $timesheets = $timesheets->map(function ($timesheet) {
                $timesheet->detected_image_array = [$timesheet->detected_image_url];
                return $timesheet;
            });

            $log = $this->getLogTimesheetByUserDate($requestDatas);
            
            $petitions = Petition::select(
                'id',
                'start_date',
                'end_date',
                'type',
                'type_off',
                'type_paid',
                'type_go_out',
                'start_time',
                'end_time',
                'start_time_change',
                'end_time_change',
                'reason',
                'status',
                'infringe',
                'rejected_reason'
            )
            ->where('user_id', $requestDatas['user_id'])
            ->where(function ($query) use ($requestDatas) {
                $query->where('start_date', $requestDatas['date']);
                $query->orWhere(function ($query) use ($requestDatas) {
                    $query->where('start_date', '<=', $requestDatas['date']);
                    $query->where('end_date', '>=', $requestDatas['date']);
                });
            })
            ->whereNull('deleted_at')
            ->get();

            

            $data = [
                'timesheets' => $timesheets,
                'log' => $log,
                'petitions' => $this->transferPetitions($petitions),
                'currentPage' => $requestDatas['current_page'],
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

    private function transferPetitions($petitions)
    {
        $newData = array();

        $petitionType = config('const.petition_type');
        $petitionTimePeriod = config('const.petition_time_period');
        $typePaid = config('const.type_paid');
        $typeGoOut = config('const.type_go_out');
        
        foreach ($petitions as $petition) {
            $typeName = $this->getTypeText($petitionType, $petition->type);
            if ($petition->type == 2) {
                $typeOffName = $this->getTypeText($petitionTimePeriod, $petition->type_off);
                $typePaidName = $this->getTypeText($typePaid, $petition->type_paid);

                $typeName .= " ".strtolower($typeOffName." ".$typePaidName);
            } elseif ($petition->type == 7) {
                $type = $petition->type_go_out ? $petition->type_go_out : 0;

                $petitionTypeIds = array_column($petitionType, 'id');
                $key = array_search($petition->type, $petitionTypeIds);

                $typeGoOutIds = array_column($typeGoOut, 'id');
                $key1 = array_search($type, $typeGoOutIds);

                $typeName = $petitionType[$key]['name']." (".$typeGoOut[$key1]['name'].")";
            }

            //get petition info
            $info = $this->getPetiotionInfo($petition);

                // $petition->status = $pentitionStatus[$petition->status];
                if ($petition->status == 0 || $petition->status == null) {
                    $petition->status = 'Chưa duyệt';
                } elseif ($petition->status == 1 && $petition->infringe == 0) {
                    $petition->status = 'Đã duyệt yêu cầu';
                } elseif ($petition->status == 1 && $petition->infringe == 1) {
                    $petition->status = 'Đã vi phạm';
                } elseif ($petition->status == 2) {
                    $petition->status = 'Bị từ chối';
                }
            //Push element onto the newData array
            $newData[] = [
                'id' => $petition->id,
                'type_name' => $typeName,
                'info' => $info,
                'reason' => $petition->reason,
                'status' => $petition->status,
                'rejected_reason' => $petition->rejected_reason,
            ];
        }

        return $newData;
    }

    /** Get Petition Type Text
     *
     * @param array $type
     * @param integer $id
     * @return string $text
    */
    private function getTypeText($type, $id)
    {
        $text = "";

        $element = array_column($type, 'id');
        $index = array_search($id, $element);
        if (strlen($index) > 0) {
            $text = $type[$index]['name'];
        }

        return $text;
    }

    /** Get Petition Info
     *
     * @param object $petition
     * @return string $info
    */
    private function getPetiotionInfo($petition)
    {
        $info = "";

        $startDate = Carbon::create($petition->start_date)->format("d/m/Y");
        $endDate = $petition->end_date ? Carbon::create($petition->end_date)->format("d/m/Y") : null;

        switch ($petition->type) {
            case (in_array($petition->type, [1,7])):
                $info = "Ngày ".$startDate." Từ ".
                    $petition->start_time."-".$petition->end_time;

                break;
            case ($petition->type == 2 && $petition->type_off == 4):
                $info = "Từ ngày ".$startDate." đến hết ngày ".$endDate;

                break;
            case (in_array($petition->type, [4,8])):
                $info = "Ngày ".$startDate." Từ ".
                    $petition->start_time."-".$petition->end_time." thành ".
                    $petition->start_time_change."-".$petition->end_time_change;

                break;
            default:
                $info = " Ngày ".$startDate;
        }

        return $info;
    }

    private function getLogTimesheetByUserDate($requestDatas)
    {
        $sql = "select ";

        $sql .= " users.fullname,";

        $sql .= " timesheet_details.date as date,";
        $sql .= " min(timesheet_details.time)";
        // $sql .= " min(timesheet_details.time) filter";
        // $sql .= " (where timesheet_details.device_id = '".self::DEVICE_CHECKIN_ID."')";
        $sql .= " as check_in,";
        $sql .= " max(user_checkouts.check_out) as check_out,";
        $sql .= " user_checkouts.final_checkout,";

        $sql .= " min(petitions.start_time_change) as start_time,";
        $sql .= " max(petitions.end_time_change) as end_time";

        $sql .= " from timesheet_details";

        //join users table
        $sql .= " join (";
        $sql .= "users left join petitions on petitions.user_id = users.id and petitions.type = 4";
        $sql .= " and petitions.start_date = '".$requestDatas['date']."' and petitions.status = 1";
        $sql .= ")";
        $sql .= " on users.user_code = timesheet_details.user_code";
        //left join user_checkouts table
        $sql .= " left join user_checkouts on user_checkouts.user_code = timesheet_details.user_code";
        $sql .= " and user_checkouts.date = timesheet_details.date";

        //condition with where
        $where = " where";
        $where .= " timesheet_details.date = '".$requestDatas['date']."'";
        $where .= " and users.user_status != 2 and users.position != 3";
        $where .= " and users.id = '".$requestDatas['user_id']."'";

        $sql .= $where;

        $sql .= " group by users.fullname, timesheet_details.date, user_checkouts.final_checkout";

        $data = DB::select($sql);
        
        if (count($data) > 0) {
            return $data[0];
        }
        
        return $data;
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

        if (isset($requestDatas['user_id']) && !empty($requestDatas['user_id'])) {
            $addedQuery = $addedQuery->where('users.id', $requestDatas['user_id']);
        }

        if (!empty($requestDatas['date'])) {
            $addedQuery = $addedQuery->whereDate(
                'timesheet_details.date',
                '=',
                Carbon::create($requestDatas['date'])->format('Y-m-d')
            );
        }

        return $addedQuery;
    }

    public function sync(SyncTimesheetDetailRequest $request)
    {
        set_time_limit(900);

        $requestDatas = $request->all();

        try {
            //Role check
            if (Auth()->user()->permission != "1") {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            $startDate = Carbon::create($requestDatas['start_date'])->format('Y/m/d 00:00:00');
            $endDate = Carbon::create($requestDatas['end_date'])->format('Y/m/d 23:59:59');

            $partnerConfig = PartnerConfig::where('code', 'HANET')->first();

            if ($partnerConfig->setting) {
                $setting = json_decode($partnerConfig->setting);

                //get hanet access token
                $accessToken = $setting->access_token;

                //users list
                $users = $requestDatas['users'];

                //start transaction
                DB::beginTransaction();

                foreach ($users as $user) {
                    $employee = User::where('user_code', $user['user_code'])->first();

                    $data = $this->hanetRepository->getCheckinByPlaceIdInTimestamp(
                        $accessToken,
                        $employee->place_id,
                        [$requestDatas['device_code']],
                        strtotime($startDate)*1000,
                        strtotime($endDate)*1000,
                        $user['user_code']
                    );

                    if ($data) {
                        if ($data->returnCode == 1 && is_array($data->data)) {
                            $this->saveEmployeeLog($data->data, $user['user_code'], $requestDatas);
                        }
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => __('MSG-S-001'),
                ], Response::HTTP_OK);
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function saveEmployeeLog($data, $userCode, $requestDatas)
    {
        $date = "";
                        
        foreach ($data as $value) {
            if ($value->date != $date) {
                $date = $value->date;

                //delete old timesheet details
                TimesheetDetail::where([
                    'user_code' => $userCode,
                    'date' => $value->date,
                    'device_id' => $requestDatas['device_code']
                ])->delete();
            }

            //insert timesheet details
            TimesheetDetail::create([
                'user_code' => $value->aliasID != "" ? $value->aliasID : $userCode,
                'detected_image_url' => $value->avatar,
                'device_id' => $value->deviceID,
                'person_title' => $value->title,
                'time_int' => $value->checkinTime/1000,
                'time' => date('H:i:s', $value->checkinTime/1000),
                'date' => date('Y-m-d', $value->checkinTime/1000),
                'json_data' => json_encode($value),
            ]);
        }
    }
}
