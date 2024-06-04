<?php

namespace App\Http\Controllers\api\Petition;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Petition;
use App\Models\User;
use App\Models\UserAlert;
use App\Models\UserCheckout;
use App\Models\UserGoOut;
use App\Http\Requests\api\Petition\GetPetitionListRequest;
use App\Http\Requests\api\Petition\GetPetitionByIdRequest;
use App\Http\Requests\api\Petition\PetitionRegisterRequest;
use App\Http\Requests\api\Petition\CheckPetitionInfringeRequest;
use App\Http\Requests\api\Petition\PetitionEditRequest;
use App\Http\Requests\api\Petition\PetitionActionRequest;
use App\Http\Requests\api\Petition\PetitionDeleteRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Petition API
 *
 * @group Petition
 */
class PetitionController extends Controller
{
    /** Petition list
     *
     *
     * @group Petition
     *
     * @bodyParam fullname string optional (Employee's Fullname)
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
    public function getPetitionList(GetPetitionListRequest $request)
    {
        try {
            $query = Petition::query()
                ->leftJoin('users', 'users.id', '=', 'petitions.user_id')
                ->select(DB::raw('petitions.id as id,
                                users.fullname as fullname,
                                users.id as user_id,
                                petitions.type as type,
                                petitions.type_off as type_off,
                                petitions.type_go_out as type_go_out,
                                petitions.type_paid as type_paid,
                                petitions.start_time as start_time,
                                petitions.end_time as end_time,
                                petitions.start_time_change as start_time_change,
                                petitions.end_time_change as end_time_change,
                                petitions.start_date as start_date,
                                petitions.end_date as end_date,
                                petitions.reason as reason,
                                petitions.status as status,
                                petitions.infringe as infringe,
                                petitions.created_at as created_at,
                                petitions.updated_at as updated_at,
                                petitions.approve_pm',
                ))
                ->orderBy('petitions.created_at', 'desc');

            //Add SQL according to requested search conditions
            //On request
            $requestDatas = $request->all();

            $query = $this->addSqlWithSorting($requestDatas, $query);

            //db query
            if (Auth()->user()->id === 51 && $requestDatas['status'] == 0) {
                // $query->where('petitions.approve_pm', true);
                $query->where('users.department_id', '!=', 12);
            }
            // if (Auth()->user()->id === 51 && $requestDatas['status'] == 0) {
            //     $query->where(function($subQuery) {
            //         $subQuery->where('petitions.approve_pm', true)
            //                 ->orWhere('petitions.user_id', 51)
            //                  ->Where(function($subSubQuery) {
            //                      $subSubQuery ->where('petitions.status', 0)
            //                                   ->orWhere('petitions.status', null);
            //                  });
            //     });
            // }
            $total = $query->get()->count();
            if ($total/$requestDatas['per_page'] < $requestDatas['current_page']) {
                $requestDatas['current_page'] = ceil($total/$requestDatas['per_page']);
            }
            $petitions = $query->orderBy('petitions.created_at', 'DESC')
                            ->offset(($requestDatas['current_page'] - 1) * $requestDatas['per_page'])
                            ->limit($requestDatas['per_page'])
                            ->get();

            //no search results
            if (count($petitions) === 0) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $data = [
                'items' => $this->transferData($petitions),
                'currentPage' => $requestDatas['current_page'],
                'perPage' => $requestDatas['per_page'],
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

    public function getPetitionById(GetPetitionByIdRequest $request)
    {
        try {
            $petition = Petition::where('id', $request->id)->first();

            $data = [
                'id' => $petition->id,
                'user_id' => $petition->user_id,
                'start_date' => Carbon::create($petition->start_date)->format('Y/m/d'),
                'end_date' => null,
                'reason' => $petition->reason,
                'type' => $petition->type,
                'type_off' => $petition->type_off,
                'type_paid' => $petition->type_paid,
                'type_go_out' => $petition->type_go_out,
                'updated_at' => $petition->updated_at
            ];

            if ($petition->type_off == 4) {
                $data['end_date'] = Carbon::create($petition->end_date)->format('Y/m/d');
            }

            if ($petition->type != 2) {
                $data['start_time'] = $petition->start_time;
                $data['end_time'] = $petition->end_time;
                if (in_array($petition->type, [4,8])) {
                    $data['start_time'] = $petition->start_time_change;
                    $data['end_time'] = $petition->end_time_change;
                }
            }

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
        }
    }

    /** Select boxes data for create/update petition
     *
     *
     * @group Petition
     *
     *
     * @response 200 {
     *  [
     *      "projects": [
     *          {
     *              "id": 1,
     *              "project_name": "WZ 0.0.1"
     *          },
     *          {
     *              "id": 2,
     *              "project_name": "Battle Joke"
     *          },
     *          ...
     *      ],
     *  ]
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function getSelectboxes()
    {
        try {
            $petitionType = config('const.petition_type');
            $timePeriod = config('const.petition_time_period');
            $typePaid = config('const.type_paid');
            $typeGoOut = config('const.type_go_out');
            $fullPermission = config('const.petitions_full_permission');

            $user = Auth()->user();
            //userSelbox
            $users = User::query()
                ->select(DB::raw('users.id as id,
                                    users.fullname as fullname'))
                ->where('users.user_status', '!=', 2)
                ->where(function ($query) use ($user, $fullPermission) {
                    if ($user->position == 1) {
                        $query->where('department_id', $user->department_id);
                    } elseif (!in_array($user->id, $fullPermission)) {
                        $query->where('id', $user->id);
                    }
                })
                ->get();

            $isAuthority = false;
            $isLeader = false;
            if (in_array($user->id, $fullPermission)) {
                $isAuthority = true;
            }
            if ($user->position == 1) {
                $isLeader = true;
            }
            $userLogin = [
                'id' => Auth()->user()->id,
                'is_authority' => $isAuthority,
                'is_leader' => $isLeader
            ];

            $data = [
                'petition_type' => $petitionType,
                'time_period' => $timePeriod,
                'type_paid' => $typePaid,
                'type_go_out' => $typeGoOut,
                'users' => $users,
                'user_login' => $userLogin
            ];

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Petition Store
     *
     * @group Petition
     *
     * @bodyParam type integer required Loại yêu cầu
     * @bodyParam type_off integer required Loại ngày nghỉ
     * @bodyParam type_paid integer required Hình thức nghỉ phép
     * @bodyParam user_id integer required Tên nhân viên
     * @bodyParam start_time time required Thời gian bắt đầu
     * @bodyParam end_time time required Thời gian kết thúc
     * @bodyParam start_date date required Ngày bắt đầu
     * @bodyParam end_date date required Ngày kết thúc
     * @bodyParam reason string required Lý do
     *
     * @response 404 {
     *    'status' : 404,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 422 {
     *      "status": 422,
     *      "errors": "Loại yêu cầu không tồn tại trên hệ thống",
     *      "errors_list": {
     *          "type": [
     *              "Loại yêu cầu không tồn tại trên hệ thống"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function store(PetitionRegisterRequest $request)
    {
        $requestDatas = $request->all();

        $startTimeAM = config('const.start_time_am');
        $endTimeAM = config('const.end_time_am');
        $startTimePM = config('const.start_time_pm');
        $endTimePM = config('const.end_time_pm');

        try {
            $message = "";
            $infringe = 0;
    
            if ($requestDatas['type'] == 2) {
                //check petition infringe
                $message = $this->getInfringe($requestDatas);

                if ($message) {
                    $infringe = 1;
                }
            }

            //start transaction control
            DB::beginTransaction();

            $petition = Petition::create([
                'type' =>  $requestDatas['type'],
                'type_off' => isset($requestDatas['type_off']) ? $requestDatas['type_off'] : null,
                'type_paid' => isset($requestDatas['type_paid']) ? $requestDatas['type_paid'] : null,
                'user_id' => $requestDatas['user_id'],
                'reason' => $requestDatas['reason'],
                'start_time' => isset($requestDatas['start_time']) ? $requestDatas['start_time'] : null,
                'end_time' => isset($requestDatas['end_time']) ? $requestDatas['end_time'] : null,
                'type_go_out' => isset($requestDatas['type_go_out']) ? $requestDatas['type_go_out'] : null,
                'user_go_out_id' => isset($requestDatas['user_go_out_id']) ? $requestDatas['user_go_out_id'] : null,
                'start_time_change' => isset($requestDatas['start_time_change']) ? $requestDatas['start_time_change'] : null,
                'end_time_change' => isset($requestDatas['end_time_change']) ? $requestDatas['end_time_change'] : null,
                'start_date' => $requestDatas['start_date'],
                'end_date' => isset($requestDatas['end_date']) ? $requestDatas['end_date'] : null,
                'infringe' => $infringe
            ]);

            //init entry data to insert to user_alerts table
            $entry = [
                'user_id' => Auth()->user()->id,
                'action' => 'created',
                'resource_type' => 'Petition',
                'resource_id' => $petition->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
            // Insert the data into the user_works table
            UserAlert::insert($entry);

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-002'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    /** Petition's action
     *
     * @group Petition
     *
     * @bodyParam id bigint required ID Petition
     * @bodyParam key integer required
     * @bodyParam check_updated_at date required
     *
     * * @response 400 {
     *    'status' : 400,
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
     *      "errors": "Đơn yêu cầu không tồn tại",
     *      "errors_list": {
     *          "id": [
     *              "Đơn yêu cầu không tồn tại"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function action(PetitionActionRequest $request)
    {
        $requestDatas = $request->all();

        try {
            //Role check
            if (Auth()->user()->position < 2 && Auth()->user()->permission < 1) {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            $petition = Petition::findOrFail($requestDatas['id']);

            //exclusion control
            $petition->setCheckUpdatedAt($requestDatas['check_updated_at']);

            //start transaction control
            DB::beginTransaction();

            switch ($requestDatas['key']) {
                case 1:
                    $petition->status = 1;
                    //approve this petition, if infringe is 1 we'll update it to 0
                    if ($petition->infringe > 0) {
                        $petition->infringe = 0;
                    }

                    if ($petition->end_time_change) {
                        $user = User::findOrFail($petition->user_id);

                        //update time checkout to user_checkouts table
                        $log = UserCheckout::where('user_checkouts.user_code', $user->user_code)
                                            ->whereDate('user_checkouts.date', $petition->start_date)
                                            ->first();

                        if (!$log) {
                            $checkout = UserCheckout::create([
                                'user_code' => $user->user_code,
                                'check_out' => $petition->end_time_change,
                                'date' => $petition->start_date
                            ]);

                            //log the record that has been saved successfully
                            $message = 'user_id: '.$petition->user_id.' has been inserted log checkout. Id checkout: ';
                            Log::info($message.$checkout->id);
                        }
                    }

                    //the petition is change log go outs
                    if ($petition->type == 8) {
                        $record = UserGoOut::findOrFail($petition->user_go_out_id);

                        $record->start_time = $petition->start_time_change;
                        $record->end_time = $petition->end_time_change;
                        $record->status = null;

                        $record->save();
                    }
                    break;
                case 2:
                    $petition->status = 1;
                    //Approve infringement
                    // if ($petition->infringe < 1) {
                    $petition->infringe = 1;
                    // }
                    break;
                case 3:
                    //Decline
                    $petition->status = 2;
                    $requestDatas['rejected_reason'] && $requestDatas['rejected_reason'] != '' ? $petition->rejected_reason = $requestDatas['rejected_reason'] : '';
                    break;

                default:
                    # code...
                    break;
            }

            $petition->save();

            //init entry data to insert to user_alerts table
            $entry = [
                'user_id' => Auth()->user()->id,
                'action' => 'updated',
                'resource_type' => 'Petition',
                'resource_id' => $petition->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ];
            // Insert the data into the user_works table
            UserAlert::insert($entry);

            DB::commit();
        } catch (ExclusiveLockException $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Update Petition
     *
     * @group Petition
     *
     * @bodyParam id bigint required ID Petition
     * @bodyParam check_updated_at date required
     * @bodyParam type integer required Loại yêu cầu
     * @bodyParam type_off integer depending Loại ngày nghỉ
     * @bodyParam type_paid integer depending Hình thức nghỉ phép
     * @bodyParam user_id integer required Tên nhân viên
     * @bodyParam start_time time depending Thời gian bắt đầu
     * @bodyParam end_time time depending Thời gian kết thúc
     * @bodyParam start_date date required Ngày bắt đầu
     * @bodyParam end_date date depending Ngày kết thúc
     * @bodyParam reason string required Lý do
     *
     * * @response 400 {
     *    'status' : 400,
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
     *      "errors": "Đơn yêu cầu không tồn tại",
     *      "errors_list": {
     *          "id": [
     *              "Đơn yêu cầu không tồn tại"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function update(PetitionEditRequest $request)
    {
        $requestDatas = $request->all();

        try {
            //get const time
            $startTimeAM = config('const.start_time_am');
            $endTimeAM = config('const.end_time_am');
            $startTimePM = config('const.start_time_pm');
            $endTimePM = config('const.end_time_pm');

            $petition = Petition::findOrFail($requestDatas['id']);

            //Role check
            if ($petition->status && Auth()->user()->position < 2 && Auth()->user()->permission < 1) {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            //exclusion control
            $petition->setCheckUpdatedAt($requestDatas['check_updated_at']);

            $startTime = null;
            $endTime = null;
            $startTimeChange = null;
            $endTimeChange = null;
            $typeGoOut = null;

            //start transaction control
            DB::beginTransaction();

            $type = $requestDatas['type'];
            $typeOff = $requestDatas['type_off'];
            switch ($type) {
                case (in_array($type, [1,5,6,7])):
                    $startTime = $requestDatas['start_time'];
                    $endTime = $requestDatas['end_time'];

                    $typeGoOut = $requestDatas['type_go_out'];
                    break;
                case ($type == 2 && $typeOff == 1):
                    $startTime = $startTimeAM;
                    $endTime = $endTimeAM;
                    break;
                case ($type == 2 && $typeOff == 2):
                    $startTime = $startTimePM;
                    $endTime = $endTimePM;
                    break;
                case ($type == 2 && $typeOff == 3):
                    $startTime = $startTimeAM;
                    $endTime = $endTimePM;
                    break;
                    case (in_array($type, [4,8])):
                    //old start time and end time
                    $startTime = $petition->start_time;
                    $endTime = $petition->end_time;

                    //new start and end time that employee wanna change it
                    $startTimeChange = $requestDatas['start_time_change'];
                    $endTimeChange = $requestDatas['end_time_change'];

                    if ($endTimeChange && $petition->status) {
                        $user = User::findOrFail($petition->user_id);

                        //update time checkout to user_checkouts table
                        $log = UserCheckout::where('user_checkouts.user_code', $user->user_code)
                                            ->whereDate('user_checkouts.date', $petition->start_date)
                                            ->first();

                        if (!$log) {
                            $checkout = UserCheckout::create([
                                'user_code' => $user->user_code,
                                'check_out' => $endTimeChange,
                                'date' => $petition->start_date
                            ]);

                            //log the record that has been saved successfully
                            $message = 'user_id: '.$petition->user_id.' has been inserted log checkout. Id checkout: ';
                            Log::info($message.$checkout->id);
                        } else {
                            $log->check_out = $endTimeChange;

                            $log->save();

                            //log the record that has been saved successfully
                            $message = 'user_id: '.$petition->user_id.' has been updated log checkout. Id checkout: ';
                            Log::info($message.$log->id);
                        }
                    }
                    break;
                case 9:
                    $startTime = $requestDatas['start_time'];
                    $endTime = $requestDatas['end_time'];
                    break;
                default:
            }

            $petition->type = $requestDatas['type'];
            $petition->type_off = isset($requestDatas['type_off']) ? $requestDatas['type_off'] : null;
            $petition->type_paid = isset($requestDatas['type_paid']) ? $requestDatas['type_paid'] : null;
            $petition->start_date = $requestDatas['start_date'];
            $petition->end_date = isset($requestDatas['end_date']) ? $requestDatas['end_date'] : null;

            $infringeMessage = "";
            $infringe = 0;
    
            if ($requestDatas['type'] == 2) {
                //check petition infringe
                $infringeMessage = $this->getInfringe($requestDatas, $petition->created_at);

                $infringe = $infringeMessage ? 1 : 0;
            }

            $petition->user_id = $requestDatas['user_id'];
            $petition->reason = $requestDatas['reason'];
            $petition->start_time = $startTime;
            $petition->end_time = $endTime;
            $petition->type_go_out = $typeGoOut;
            $petition->start_time_change = $startTimeChange;
            $petition->end_time_change = $endTimeChange;
            $petition->infringe = $infringe;

            $petition->save();

            DB::commit();
        } catch (ExclusiveLockException $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Delete petition
     *
     * @group Petition
     *
     * @bodyParam id bigint required
     * @bodyParam check_updated_at date required
     * @bodyParam key integer required
     *
     * @response 400 {
     *    'status' : 400,
     *    "errors": "Dữ liệu đã thay đổi ở thiết bị khác và không thể cập nhật"
     * }
     *
     * @response 404 {
     *    'status' : 404,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 422 {
     *    'status' : 422,
     *    "errors": "Ngày giờ sửa đổi không được để trống",
     *    "errors_list": {
     *          "check_updated_at": [
     *              "Ngày giờ sửa đổi không được để trống"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function destroy(PetitionDeleteRequest $request)
    {
        $requestDatas = $request->all();

        try {
            //Role check
            if (Auth()->user()->position < 2 && Auth()->user()->permission < 1) {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            $petition = Petition::findOrFail($requestDatas['id']);
            //exclusion control
            $petition->setCheckUpdatedAt($requestDatas['check_updated_at']);

            //start transaction
            DB::beginTransaction();

            //delete petitions
            if (Auth()->user()->id == 107) {
                $petition->forceDelete();
            } else {
                //init entry data to insert to user_alerts table
                $entry = [
                    'user_id' => Auth()->user()->id,
                    'action' => 'deleted',
                    'resource_type' => 'Petition',
                    'resource_id' => $petition->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
                // Insert the data into the user_works table
                UserAlert::insert($entry);

                $petition->delete();
            }

            DB::commit();
        } catch (ExclusiveLockException $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'errors' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Check Petition Infringe
     *
     * @group Petition
     *
     * @bodyParam type integer required Loại yêu cầu
     * @bodyParam type_off integer required Loại ngày nghỉ
     * @bodyParam type_paid integer required Hình thức nghỉ phép
     * @bodyParam user_id integer required Tên nhân viên
     * @bodyParam start_time_change time required Thời gian bắt đầu muốn thay đổi
     * @bodyParam end_time_change time required Thời gian kết thúc muốn thay đổi
     * @bodyParam start_date date required Ngày bắt đầu
     * @bodyParam end_date date required Ngày kết thúc
     * @bodyParam reason string required Lý do
     *
     * @response 404 {
     *    'status' : 404,
     *    'errors' : 'XXXXXXXX'
     * }
     *
     * @response 422 {
     *      "status": 422,
     *      "errors": "Loại yêu cầu không tồn tại trên hệ thống",
     *      "errors_list": {
     *          "type": [
     *              "Loại yêu cầu không tồn tại trên hệ thống"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function checkPetitionInfringe(CheckPetitionInfringeRequest $request)
    {
        //On request
        $requestDatas = $request->all();

        $message = "";

        if ($requestDatas['type'] == 2) {
            $message = $this->getInfringe($requestDatas);
        }

        return response()->json($message);
    }

    /** Get infringe
     *
     * @param request $requestDatas
     * @return string $message
    */
    private function getInfringe($requestDatas, $timeCreate = null)
    {
        $message = "";

        $typeLeave = $requestDatas['type_off'];
        $typePaid = $requestDatas['type_paid'];

        switch ($typeLeave) {
            case (in_array($typeLeave, [1,3]) && $typePaid == 0):
                //total hours have to taken before create a petition about offline without salary
                $leaveRequestTime = config('const.hour_without_salary'); //48 hours
                //start work time AM
                $startTimeAM = config('const.start_time_am');

                $message = $this->checkHours(
                    $timeCreate,
                    $requestDatas['start_date'],
                    $startTimeAM,
                    $leaveRequestTime
                );

                break;
            case (in_array($typeLeave, [1,3]) && $typePaid == 1):
                //total hours have to taken before create a petition about offline with salary
                $leaveRequestTime = config('const.hour_with_salary');
                //start work time AM
                $startTimeAM = config('const.start_time_am');

                $message = $this->checkHours(
                    $timeCreate,
                    $requestDatas['start_date'],
                    $startTimeAM,
                    $leaveRequestTime,
                );

                break;
            case ($typeLeave == 2 && $typePaid == 0):
                //total hours have to taken before create a petition about offline without salary
                $leaveRequestTime = config('const.hour_without_salary');
                //start work time PM
                $startTimePM = config('const.start_time_pm');

                $message = $this->checkHours(
                    $timeCreate,
                    $requestDatas['start_date'],
                    $startTimePM,
                    $leaveRequestTime,
                );

                break;
            case ($typeLeave == 2 && $typePaid == 1):
                //total hours have to taken before create a petition about offline with salary
                $leaveRequestTime = config('const.hour_with_salary');
                //start work time PM
                $startTimePM = config('const.start_time_pm');

                $message = $this->checkHours(
                    $timeCreate,
                    $requestDatas['start_date'],
                    $startTimePM,
                    $leaveRequestTime,
                );

                break;
            case ($typeLeave == 4 && $typePaid == 0):
                //total hours have to taken before create a petition about offline without salary
                $leaveRequestTime = config('const.hour_days_without_salary'); //168 hours
                //start work time AM
                $startTimeAM = config('const.start_time_am');

                $countDay = $this->countWorkDay(
                    Carbon::create($requestDatas['start_date'])->format('Y-m-d'),
                    Carbon::create($requestDatas['end_date'])->format('Y-m-d')
                );
                if ($countDay < 3) {
                    $leaveRequestTime = config('const.hour_without_salary'); //48 hours
                }

                $message = $this->checkHours(
                    $timeCreate,
                    $requestDatas['start_date'],
                    $startTimeAM,
                    $leaveRequestTime
                );

                break;
            case ($typeLeave == 4 && $typePaid == 1):
                //total hours have to taken before create a petition about offline with salary
                $leaveRequestTime = config('const.hour_days_with_salary'); //336 hours
                //start work time AM
                $startTimeAM = config('const.start_time_am');

                $countDay = $this->countWorkDay(
                    Carbon::create($requestDatas['start_date'])->format('Y-m-d'),
                    Carbon::create($requestDatas['end_date'])->format('Y-m-d')
                );
                if ($countDay < 3) {
                    $leaveRequestTime = config('const.hour_with_salary'); //120 hours
                }

                $message = $this->checkHours(
                    $timeCreate,
                    $requestDatas['start_date'],
                    $startTimeAM,
                    $leaveRequestTime
                );

                break;
            default:
        }

        return $message;
    }

    /** Check hours
     *
     * @param date $startDate
     * @param string $startTime
     * @param integer $constHours
     * @param boolean $check
     * @return string $message
    */
    private function checkHours($timeCreate, $startDate, $startTime, $leaveRequestTime)
    {
        $message = "";

        $date = Carbon::create($startDate)->format("Y/m/d ".$startTime);

        $startDate = new Carbon($date);
        $now = Carbon::now()->format("Y/m/d H:i:s");
        if ($timeCreate) {
            $now = $timeCreate;
        }

        $hours = $startDate->diffInHours($now);

        if ($hours < $leaveRequestTime) {
            if (!$timeCreate) {
                $message = __('MSG-E-014', [
                    'attribute' => ($leaveRequestTime/24),
                    'attribute2' => Carbon::create($startDate)->format("d/m/Y ".$startTime)
                ]);
            } else {
                $message = __('MSG-E-015', ['attribute' => ($leaveRequestTime/24)]);
            }
        }

        return $message;
    }

    private function countWorkDay($startDate, $endDate)
    {
        $count = 0;

        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period->toArray() as $item) {
            switch ($item->format('l')) {
                case 'Monday':
                case 'Tuesday':
                case 'Wednesday':
                case 'Thursday':
                case 'Friday':
                    $count += 1;
                    break;
                case 'Saturday':
                    $count += 0.5;
                    break;
                default:
                    # code...
                    break;
            }
        }

        return $count;
    }

    /** Handle columns value
     *
     * @param $petitions
     * @return $newData
    */
    private function transferData($petitions)
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

            $message = "";
            if ($petition->type == 2 && $petition->infringe == 1) {
                //get infringe message
                $message = $this->getInfringe([
                    'type' => $petition->type,
                    'type_off' => $petition->type_off,
                    'type_paid' => $petition->type_paid,
                    'start_date' => $petition->start_date,
                    'end_date' => $petition->end_date,
                ], $petition->created_at);
            }

            //Push element onto the newData array
            array_push($newData, [
                'id' => $petition->id,
                'fullname' => $petition->fullname,
                'user_id' => $petition->user_id,
                'type_name' => $typeName,
                'info' => $info,
                'reason' => $petition->reason,
                'status' => $petition->status,
                'infringe_message' => $message,
                'infringe' => $petition->infringe,
                'type_go_out' => $petition->type_go_out,
                'created_at' => Carbon::create($petition->created_at)->format("d/m/Y H:i:s"),
                'updated_at' => $petition->updated_at,
                'approve_pm' => $petition->approve_pm
            ]);
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

    /** Append SQL if json is requested
     *
     * @param  $requestDatas
     * @param  $query
     * @return $addedQuery
    */
    private function addSqlWithSorting($requestDatas, $query)
    {
        $screenRoles = config('const.petitions_full_permission');

        //user login info
        $user = Auth()->user();
        //sql
        $addedQuery = $query;

        //Admin, Leader and Accountant can see all petitions
        if (in_array($user->id, $screenRoles) || $user->position == 1) {
            if (isset($requestDatas['user_id']) && !empty($requestDatas['user_id'])) {
                $addedQuery = $addedQuery->where('petitions.user_id', $requestDatas['user_id']);
            }
        } else {
            $addedQuery = $addedQuery->where('petitions.user_id', $user->id);
        }

        //Change the SQL according to the requested search conditions
        if (isset($requestDatas['start_date']) && isset($requestDatas['end_date'])) {
            $addedQuery = $addedQuery->whereRaw(
                'case when petitions.end_date is null then petitions.start_date >= ? and petitions.start_date <= ?
                 else petitions.start_date <= ? and petitions.end_date >= ? end',
                [
                    $requestDatas['start_date'],
                    $requestDatas['end_date'],
                    $requestDatas['end_date'],
                    $requestDatas['start_date']
                ]
            );
        }

        if ($requestDatas['status'] === 4) {
            $addedQuery = $addedQuery->onlyTrashed();
        } else {
            $addedQuery = $addedQuery->where(fn ($query) => $this->applyStatusConditions($query, $requestDatas['status']));
        }

        //Petitions type paid
        if (isset($requestDatas['type_paid'])) {
            $addedQuery = $addedQuery->where('petitions.type_paid', $requestDatas['type_paid']);
        }

        return $addedQuery;
    }

    protected function applyStatusConditions($query, $status)
    {
        $statusConditions = [
            0 => fn ($query) => $query->where('petitions.status', 0)->orWhereNull('petitions.status'),
            1 => fn ($query) => $query->where('petitions.status', 1)->where('petitions.infringe', 0),
            2 => fn ($query) => $query->where('petitions.status', 1)->where('petitions.infringe', 1),
            3 => fn ($query) => $query->where('petitions.status', 2)
        ];

        return $statusConditions[$status]($query);
    }

    public function updateApprovePm(Request $request)
    {
        $requestDatas = $request->all();

        try {

            $petition = Petition::findOrFail($requestDatas['id']);

            //Role check
            if ($petition->status && Auth()->user()->position < 2 && Auth()->user()->permission < 1) {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            //start transaction control
            DB::beginTransaction();

            $petition->approve_pm = $requestDatas['approve_pm'] == 1 ? false : true;

            $petition->save();

            DB::commit();
            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (ExclusiveLockException $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_BAD_REQUEST ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
