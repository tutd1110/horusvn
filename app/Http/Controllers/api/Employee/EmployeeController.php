<?php

namespace App\Http\Controllers\api\Employee;

use App\Http\Controllers\Controller;
use App\Events\PrivateWebSocket;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserPersonalInfo;
use App\Models\UserJobDetail;
use App\Models\UserCheckout;
use App\Models\UserAlert;
use App\Models\UserAlertRead;
use App\Models\UserGoOut;
use App\Models\TimesheetDetail;
use App\Http\Requests\api\Employee\GetEmployeeListRequest;
use App\Http\Requests\api\Employee\EmployeeRegisterRequest;
use App\Http\Requests\api\Employee\GetEmployeeByIdRequest;
use App\Http\Requests\api\Employee\EmployeeEditRequest;
use App\Http\Requests\api\Employee\EmployeeDeleteRequest;
use App\Http\Requests\api\Employee\GetLogGoOutRequest;
use Carbon\Carbon;
use File;
use Storage;

use App\Http\Controllers\api\CommonController;

/**
 * Employee API
 *
 * @group Employee
 */
class EmployeeController extends Controller
{
    /** Employee checkin by HANET Camera base on HANET's webhook api
    */
    public function checkin(Request $request)
    {
        $requestDatas = $request->all();
        try {
            //data_type is checkin action
            //personType = 0 is employee checkin
            if (isset($requestDatas['data_type']) && $requestDatas['data_type'] === 'log'
            && isset($requestDatas['personType']) && $requestDatas['personType'] == 0) {
                //end work time AM
                $endTimeAM = config('const.end_time_am');
                //end work time PM
                $endTimePM = config('const.end_time_pm');

                //start transaction
                DB::beginTransaction();

                $strDateTime = strtotime($requestDatas['date']);

                //insert timesheet details
                TimesheetDetail::create([
                    'user_code' => $requestDatas['aliasID'],
                    'detected_image_url' => $requestDatas['detected_image_url'],
                    'device_id' => $requestDatas['deviceID'],
                    'time_int' => $strDateTime,
                    'time' => date('H:i:s', $strDateTime),
                    'date' => date('Y-m-d', $strDateTime),
                    'person_title' => $requestDatas['personTitle'],
                    'json_data' => json_encode($requestDatas),
                ]);

                //insert user_checkouts if time after end time PM workday (17:30)
                $dayLabel = Carbon::now()->format('l');

                $endTime = $endTimePM;
                $currentDate = Carbon::now()->format('Ymd');
                if ($dayLabel == 'Saturday' && !in_array($currentDate, ['20230107', '20230506', '20230909'])) {
                    $endTime = $endTimeAM;
                }

                if ($strDateTime >= strtotime(date('Y-m-d').' '.$endTime) || ($requestDatas['aliasID'] == 186 && $strDateTime >= strtotime(date('Y-m-d').' '."16:00:00"))) {
                    $this->saveCheckout($requestDatas);
                }

                DB::commit();
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
        }
    }

    /** After end time workday pm (17:30:00), auto save log checkout
    */
    private function saveCheckout($requestDatas)
    {
        $strDateTime = strtotime($requestDatas['date']);

        $userCheckout = UserCheckout::select('id', 'user_code', 'check_out', 'final_checkout', 'date')
                    ->where('user_code', $requestDatas['aliasID'])
                    ->where('date', date('Y-m-d', $strDateTime))
                    ->first();

        if ($userCheckout) {
            if (!$userCheckout->final_checkout) {
                $userCheckout->check_out = date('H:i:s', $strDateTime);

                $userCheckout->save();
            }
        } else {
            UserCheckout::create([
                'user_code' => $requestDatas['aliasID'],
                'check_out' => date('H:i:s', $strDateTime),
                'date' => date('Y-m-d', $strDateTime)
            ]);
        }
    }

    /** Checkin by hand for only employee Huynh Kieu Xuan Truong
    */
    public function checkInByHand()
    {
        try {
            //user check
            $user = Auth()->user();
            if ($user->id != 69) {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            $log = TimesheetDetail::where('user_code', $user->user_code)
                        ->where('date', Carbon::now()->format('Y/m/d'))
                        ->first();

            if ($log) {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            //start transaction
            DB::beginTransaction();

            //insert timesheet details
            TimesheetDetail::create([
                'user_code' => $user->user_code,
                'time_int' => time(),
                'time' => date('H:i:s', time()),
                'date' => date('Y-m-d', time()),
                'person_name' => $user->fullname,
            ]);

            DB::commit();
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
        }
    }

    /** Employee list
     *
     *
     * @group Employee
     *
     * @bodyParam fullname string optional (Employee's Fullname)
     *
     * @response 200 {
     *  [
     *      {
     *          "id": "1",
     *          "avatar": "iamadmin.png",
     *          "birthday": "2000-01-01",
     *          "check_type": 1,
     *          "date_official": "2022-12-22",
     *          ...
     *      },
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
    public function getEmployeeList(GetEmployeeListRequest $request)
    {
        try {
            //employees who can controll this screen
            $employeeScreenRole = config('const.employee_screen_role');
            $query = User::select(
                    'users.id',
                    'users.avatar',
                    'users.fullname',
                    'users.phone',
                    'users.email',
                    'users.birthday',
                    'users.department_id',
                    'users.position',
                    'users.date_official',
                    'users.date_probation',
                    'users.permission',
                    'users.created_at',
                    'users.updated_at',
                    'users.user_status',
                    'users.type',
                    'user_personal_infos.gender',
                )
                ->leftJoin('user_personal_infos', 'users.id', '=', 'user_personal_infos.user_id')
                ->orderByRaw('date_official asc, created_at asc');

            //Add SQL according to requested search conditions
            //On request
            $requestDatas = $request->all();
            if (!empty($requestDatas)) {
                //get json from request
                $query = $this->addSqlWithSorting($requestDatas, $query);
            }
            $employees = $query->get();
            //no search results
            if (count($employees) === 0) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $data = [
                "employee" => $this->handleColumnsValue($employees),
                "is_administrator" => in_array(Auth()->user()->id, $employeeScreenRole) || Auth()->user()->id == 90 ? true : false,
                "is_viewphone" => in_array(Auth()->user()->id, [46,82,51,63,90,161,232]) ? true : false
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

    /** Get Employee By Id
     *
     *
     * @group Employee
     *
     * @bodyParam id integer require (Employee's ID)
     *
     * @response 200 {
     *  [
     *      {
     *          "id": "1",
     *          "avatar": "iamadmin.png",
     *          "birthday": "2000-01-01",
     *          "check_type": 1,
     *          "date_official": "2022-12-22",
     *          ...
     *      },
     *  ]
     * }
     * @response 422 {
     *      "status": 422,
     *      "errors": "Mã Nhân viên không tồn tại",
     *      "errors_list": {
     *          "id": [
     *              "Mã Nhân viên không tồn tại"
     *          ]
     *      }
     * }
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function getEmployeeById(GetEmployeeByIdRequest $request)
    {
        try {
            //list positions
            $userStatusList = config('const.user_status');
            //url avatar sample common
            $typeCheckList = config('const.type_check');

            //Role check
            if (Auth()->user()->permission != "1") {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }

            $employee = User::where('users.id', $request->id)->join('user_personal_infos', 'users.id', '=', 'user_personal_infos.user_id')
                                ->first([
                                    'users.id',
                                    'users.avatar',
                                    'users.fullname',
                                    'users.phone',
                                    'users.email',
                                    'users.birthday',
                                    'users.department_id',
                                    'users.position',
                                    'users.date_official',
                                    'users.date_probation',
                                    'users.permission',
                                    'users.user_status',
                                    'users.check_type',
                                    'users.type',
                                    'users.created_at',
                                    'user_personal_infos.gender'
                                ]);
            $employee->birthdayDMY = Carbon::create($employee->birthday)->format('d/m/Y');
            if (!empty($employee->date_official)) {
                $employee->dateOfficialDMY = Carbon::create($employee->date_official)->format('d/m/Y');
            }
            if (!empty($employee->date_probation)) {
                $employee->dateProbationDMY = Carbon::create($employee->date_probation)->format('d/m/Y');
            }
            $employee->createdAtDMY = Carbon::create($employee->created_at)->format('d/m/Y');

            $avatarFolder = config('const.avatar_file_folder');
            $avatar = $employee->avatar;
            $employee->avatar_path = $avatarFolder.'/'.$avatar;

            $data = [
                "employee" => $employee,
                "user_status" => $userStatusList,
                "type_check" => $typeCheckList
            ];

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
        }
    }

    /** Add new Employee
     *
     * @group Employee
     *
     *
     * @bodyParam avatar file required Ảnh đại diện
     * @bodyParam fullname string required Họ và tên
     * @bodyParam phone string required Số điện thoại
     * @bodyParam birthday date required Ngày sinh
     * @bodyParam email email required Email
     * @bodyParam department_id integer required Bộ phận
     * @bodyParam position string required Chức danh
     * @bodyParam permission string required Quyền truy cập
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
     *      "errors": "Ảnh đại diện không được để trống<br>Ngày sinh không đúng định dạng",
     *      "errors_list": {
     *          "avatar": [
     *              "Ảnh đại diện không được để trống"
     *          ],
     *          "birthday": [
     *              "Ngày sinh không đúng định dạng"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function store(EmployeeRegisterRequest $request)
    {
        $requestDatas = $request->all();
        $avatar = $requestDatas['avatar'];

        try {
            //Role check
            if (Auth()->user()->permission != "1") {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
            }
            //create file name
            $avatarName = time().'_'.$avatar->getClientOriginalName();

            //create an upload file folder
            $fileFolder = $this->createUpFileFolders();
            $path = public_path($fileFolder.'/'.$avatarName);

            //resize avatar and save it to public folder
            $bytesWritten = $this->resizeImageAndSave(
                $avatar,
                $requestDatas['avatar_width'],
                $requestDatas['avatar_height'],
                $requestDatas['avatar_top'],
                $requestDatas['avatar_left'],
                $path
            );

            //start transaction
            DB::beginTransaction();

            if ($bytesWritten !== false) {
                //insert employee
                $user = User::create([
                    'fullname' => $requestDatas['fullname'],
                    'avatar' => $avatarName,
                    'phone' => $requestDatas['phone'],
                    'email' => $requestDatas['email'],
                    'birthday' => $requestDatas['birthday'],
                    'department_id' => $requestDatas['department_id'],
                    'position' => $requestDatas['position'],
                    'permission' => $requestDatas['permission'],
                    'password' => Hash::make($requestDatas['password']),
                    'created_at' => $requestDatas['created_at'],
                    'check_type' => 1,
                    'user_status' => 1,
                    'type' => $requestDatas['type']
                ]);

                $userInfo = new UserPersonalInfo();
                $userInfo->gender = $request->gender ?? null;

                if ($user) {
                    $userInfo->user_id = $user->id;
                    $userInfo->fullname = $user->fullname;
                    $userInfo->birthday = $user->birthday;
                    $userInfo->phone = $user->phone;

                    UserJobDetail::create([
                        'user_id' => $user->id,
                        'position' => $user->position,
                        'department_id' => $user->department_id,
                        'start_date' => Carbon::create($user->created_at)->format('Y-m-d'),
                        'official_start_date' => null
                    ]);
                }

                $userInfo->save();
            }

            DB::commit();
        } catch (Exception $e) {
            // Check if the file exists
            if (File::exists($path)) {
                // Delete the file
                File::delete($path);
            }
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function resizeImageAndSave(
        $avatar,
        $width,
        $height,
        $top,
        $left,
        $path
    ) {
        $bytesWritten = false;
        //get file extension
        $type = exif_imagetype($avatar);
        $allowedTypes = array(
            2,// [] jpg
            3,// [] png
        );

        $bytesWritten = false;
        // Load the original image
        switch ($type) {
            case 2:
                $image = imagecreatefromjpeg($avatar);

                break;
            case 3:
                $image = imagecreatefrompng($avatar);

                break;
        }

        //set coordinates
        $cropWidth = $width;
        $cropHeight = $height;
        $cropX = $left;
        $cropY = $top;

        // Create a new image with the new dimensions
        $croppedImage = imagecreatetruecolor($cropWidth, $cropHeight);

        imagecopyresampled(
            $croppedImage,
            $image,
            0,
            0,
            $cropX,
            $cropY,
            $cropWidth,
            $cropHeight,
            $cropWidth,
            $cropHeight
        );

        //save the resized image to the public folder
        ob_start();
        if ($type == 2) {
            imagejpeg($croppedImage, null, 75);
        } elseif ($type == 3) {
            imagepng($croppedImage);
        }
        $imageData = ob_get_clean();

        //save it
        $bytesWritten = File::put($path, $imageData);

        //free up memory
        imagedestroy($image);
        imagedestroy($croppedImage);

        return $bytesWritten;
    }

    /** Update Employee
     *
     * @group Employee
     *
     *
     * @bodyParam avatar file required Ảnh đại diện
     * @bodyParam fullname string required Họ và tên
     * @bodyParam phone string required Số điện thoại
     * @bodyParam birthday date required Ngày sinh
     * @bodyParam email email required Email
     * @bodyParam department_id integer required Bộ phận
     * @bodyParam position string required Chức danh
     * @bodyParam permission string required Quyền truy cập
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
     *      "errors": "Ảnh đại diện không được để trống<br>Ngày sinh không đúng định dạng",
     *      "errors_list": {
     *          "avatar": [
     *              "Ảnh đại diện không được để trống"
     *          ],
     *          "birthday": [
     *              "Ngày sinh không đúng định dạng"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function update(EmployeeEditRequest $request)
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

            // $employee = User::findOrFail($requestDatas['id']);
            $employee = User::with('personalInfo')
            ->where('id', $requestDatas['id'])
            ->first();

            //exclusion control
            $employee->setCheckUpdatedAt($requestDatas['check_updated_at']);
            $oldAvatar = $employee->avatar;

            //start transaction
            DB::beginTransaction();

            //insert employee
            if ($request->file('avatar')) {
                $avatar = $request->file('avatar');
                $avatarName = time().'_'.$avatar->getClientOriginalName();

                $employee->avatar = $avatarName;
            }

            $employee->fullname = $requestDatas['fullname'];
            $employee->phone = $requestDatas['phone'];
            $employee->email = $requestDatas['email'];
            $employee->birthday = $requestDatas['birthday'];
            $employee->personalInfo->birthday = $requestDatas['birthday'];
            $employee->personalInfo->gender = $requestDatas['gender'];
            $employee->department_id = $requestDatas['department_id'];
            $employee->position = $requestDatas['position'];
            $employee->permission = $requestDatas['permission'];
            $employee->check_type = $requestDatas['check_type'];
            $employee->user_status = $requestDatas['user_status'];
            $employee->date_official = $requestDatas['date_official'] ? $requestDatas['date_official'] : null;
            $employee->date_probation = $requestDatas['date_probation'] ? $requestDatas['date_probation'] : null;
            $employee->created_at = $requestDatas['created_at'];
            $employee->type = $requestDatas['type'];
            if (!empty($requestDatas['password'])) {
                $employee->password = Hash::make($requestDatas['password']);
            }
            $employee->personalInfo->save();
            if ($employee->save()) {
                if ($request->file('avatar')) {
                    //create an upload file folder
                    $fileFolder = $this->createUpFileFolders();
                    
                    //delete old avatar
                    if (File::exists($fileFolder.'/'.$oldAvatar)) {
                        File::delete($fileFolder.'/'.$oldAvatar);
                    }

                    $path = public_path($fileFolder.'/'.$avatarName);
                    //resize avatar and save it to public folder
                    $this->resizeImageAndSave(
                        $avatar,
                        $requestDatas['avatar_width'],
                        $requestDatas['avatar_height'],
                        $requestDatas['avatar_top'],
                        $requestDatas['avatar_left'],
                        $path
                    );
                }
            }

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

    /** Delete employee
     *
     * @group Employee
     *
     * @bodyParam id bigint required Mã nhân viên
     * @bodyParam check_updated_at date required
     *
     * * @response 400 {
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
    public function destroy(EmployeeDeleteRequest $request)
    {
        $requestDatas = $request->all();

        //Role check
        if (Auth()->user()->permission != 1) {
            return response()->json([
                'status' => Response::HTTP_FORBIDDEN,
                'errors' => '',
            ], Response::HTTP_FORBIDDEN);
        }

        $employee = User::findOrFail($requestDatas['id']);
        //exclusion control
        $employee->setCheckUpdatedAt($requestDatas['check_updated_at']);

        $fileFolder = config('const.avatar_file_folder');
        $fileFolderPath = public_path($fileFolder);

        try {
            //start transaction
            DB::beginTransaction();

            //delete employee
            if ($employee->delete()) {
                //delete avatar
                if (File::exists($fileFolderPath.'/'.$employee->avatar)) {
                    File::delete($fileFolderPath.'/'.$employee->avatar);
                }
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

    public function checkOut()
    {
        try {
            $userCheckout = UserCheckout::leftJoin('users', 'users.user_code', '=', 'user_checkouts.user_code')
                        ->select('user_checkouts.id', 'user_checkouts.user_code', 'final_checkout')
                        ->where('users.id', Auth()->user()->id)
                        ->whereDate('date', Carbon::create(Carbon::now())->format('Y-m-d'))
                        ->first();

            //start transaction
            DB::beginTransaction();

            if (!$userCheckout) {
                UserCheckout::create([
                    'user_code' => Auth()->user()->user_code,
                    'check_out' => Carbon::create(Carbon::now())->format('H:i:s'),
                    'final_checkout' => true,
                    'date' => Carbon::create(Carbon::now())->format('Y-m-d')
                ]);
            } else {
                if ($userCheckout->final_checkout) {
                    return response()->json([
                        'status' => Response::HTTP_FORBIDDEN,
                        'errors' => '',
                    ], Response::HTTP_FORBIDDEN);
                }

                $userCheckout->check_out = Carbon::create(Carbon::now())->format('H:i:s');
                $userCheckout->final_checkout = true;

                $userCheckout->save();
            }

            DB::commit();

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

    public function goOut()
    {
        try {
            $user = UserGoOut::leftJoin('users', 'users.user_code', '=', 'user_go_outs.user_code')
                        ->select('user_go_outs.id')
                        ->where('users.id', Auth()->user()->id)
                        ->where('user_go_outs.status', 1) //employee is out of work
                        ->whereDate('date', Carbon::create(Carbon::now())->format('Y-m-d'))
                        ->first();

            //start transaction
            DB::beginTransaction();

            if (!$user) {
                UserGoOut::create([
                    'user_code' => Auth()->user()->user_code,
                    'start_time' => Carbon::create(Carbon::now())->format('H:i:s'),
                    'date' => Carbon::create(Carbon::now())->format('Y-m-d'),
                    'status' => 1
                ]);
            }

            DB::commit();

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

    public function getIn()
    {
        try {
            $user = UserGoOut::leftJoin('users', 'users.user_code', '=', 'user_go_outs.user_code')
                        ->select(
                            'user_go_outs.id',
                            'user_go_outs.end_time',
                            'user_go_outs.status',
                        )
                        ->where('users.id', Auth()->user()->id)
                        ->where('user_go_outs.status', 1) //employee is out of work
                        ->whereDate('date', Carbon::create(Carbon::now())->format('Y-m-d'))
                        ->first();

            //start transaction
            DB::beginTransaction();

            if ($user) {
                $user->end_time = Carbon::create(Carbon::now())->format('H:i:s');
                $user->status = null;

                $user->save();
            }

            DB::commit();

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

    /** Select boxes data
     *
     *
     * @group Employee
     *
     *
     * @response 200 {
     *  [
     *      "permissions": [
     *          "Nhân viên,
     *          "Admin"
     *      ],
     *      "departments": [
     *          "Admin",
     *          "Dev",
     *          ...
     *      ],
     *      "positions": [
     *          "Nhân viên",
     *          "Leader",
     *          ...
     *      ],
     *      "avatar_sample_path": "avatar/avt.jpg"
     *  ]
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function getSelectBoxes()
    {
        //list permission
        $permissions = config('const.permissions');
        //list departments
        $departments = config('const.departments');
        //list positions
        $positions = config('const.positions');
        //url avatar sample common
        $avatarSamplePath = config('const.avatar_sample_path');
        //list user type
        $userTypes = config('const.user_type');

        $selectBoxes = [
            'permissions'=> $permissions,
            'departments' => $departments,
            'positions' => $positions,
            'avatar_sample_path' => $avatarSamplePath,
            'user_type' => $userTypes
        ];

        return response()->json($selectBoxes);
    }

    public function getLogEmployeeOuts(GetLogGoOutRequest $request)
    {
        $requestDatas = $request->all();

        try {
            $logs = UserGoOut::leftJoin('users', 'users.user_code', '=', 'user_go_outs.user_code')
                    ->select(
                        'user_go_outs.id',
                        'user_go_outs.start_time',
                        'user_go_outs.end_time',
                        'user_go_outs.date',
                        'users.fullname',
                    )
                    ->where('users.id', $requestDatas['user_id'])
                    ->whereDate('date', $requestDatas['date'])
                    ->get();

            return response()->json($logs);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getNotifications()
    {
        try {
            $petitionTypes = config('const.petition_type');
            $labels = [
                'created' => [
                    'DeadlineModification' => 'đã tạo yêu cầu chỉnh sửa deadline.',
                    'Petition' => 'đã tạo yêu cầu '
                ],
                'updated' => [
                    'DeadlineModification' => 'yêu cầu chỉnh sửa deadline ',
                    'Petition' => 'yêu cầu ',
                    'status' => [
                        1 => 'duyệt ',
                        2 => 'từ chối ',
                        3 => 'duyệt không vi phạm '
                    ]
                ],
                'deleted' => [
                    'DeadlineModification' => 'đã xóa yêu cầu chỉnh sửa deadline ',
                    'Petition' => 'đã xóa yêu cầu ',
                ],
            ];

            // Define the select clause as a string
            $select = 'user_alerts.id as id,
            COALESCE(uareads.read_date, 0) as read_date,
            users.fullname as fullname,
            users.avatar as avatar,
            user_alerts.created_at as created_at,
            user_alerts.resource_type as type,
            user_alerts.action as action,
            petitions.user_id as p_user_id,
            petitions.type as p_type,
            petitions.status as p_status,
            petitions.approve_pm,
            deadline_modifications.user_id as d_user_id,
            deadline_modifications.original_deadline as d_original_deadline,
            deadline_modifications.requested_deadline as d_requested_deadline,
            deadline_modifications.status as d_status,
            resource_users.fullname as resource_fullname';
            $alerts = $this->queryGetNotifications($select, true);

            $alerts = $alerts->map(function ($alert) use ($labels, $petitionTypes) {

                if ($alert->action === 'created' && $alert->approve_pm == false && Auth()->user()->id === 51) {
                    return null;
                }

                $isModified = false;
                if ($alert->action === 'created') {
                    $description = $labels[$alert->action][$alert->type];
                } elseif ($alert->action === 'updated') {
                    $isModified = true;

                    $alertStatus = $alert->p_status ? $alert->p_status : $alert->d_status;
                    // Check if $alertStatus is null or empty
                    if (empty($alertStatus)) {
                        return null; // Skip this alert
                    }
                    $statusLabel = $labels[$alert->action]['status'][$alertStatus];

                    $description = 'đã ' . $statusLabel . $labels[$alert->action][$alert->type];
                } elseif ($alert->action === 'deleted') {
                    $isModified = true;

                    $description = $labels[$alert->action][$alert->type];
                }

                $href = "/tasks/deadline-modification";
                if ($alert->type === 'Petition') {
                    $typeInfo = collect($petitionTypes)->firstWhere('id', $alert->p_type);
                    $petitionLabel = $typeInfo ? $typeInfo['name'] : 'Unknown';
                    $description .= $petitionLabel;

                    $href = "/petitions";
                }

                if ($isModified) {
                    $description .= ' của ' .$alert->resource_fullname;
                }
                $avatar = $alert->avatar;
                $data = [
                    'id' => $alert->id,
                    'read_date' => $alert->read_date,
                    'username' => $alert->fullname,
                    'avatar' => $avatar,
                    'created_at' => Carbon::parse($alert->created_at)->format('Y/m/d H:i:s'),
                    'description' => $description,
                    'href' => $href
                ];

                return $data;
            });

            // Remove null values (skipped alerts) from the resulting array
            $alerts = $alerts->filter(function ($alert) {
                return !is_null($alert);
            })->values();
            
            return response()->json($alerts);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function readNotification(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $alert = UserAlert::findOrFail($requestDatas['id']);

            $read = UserAlertRead::where('user_id', Auth()->user()->id)
                    ->where('user_alert_id', $alert->id)
                    ->first();

            if ($read) {
                $read->read_date = Carbon::now()->timestamp;
                $read->save();
            } else {
                UserAlertRead::create([
                    'user_id' => Auth()->user()->id,
                    'user_alert_id' => $alert->id,
                    'read_date' => Carbon::now()->timestamp,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function readAllNotifications(Request $request)
    {
        try {
            $userId = Auth()->user()->id;
            // Define the select clause as a string
            $select = 'user_alerts.id as id';
            $alerts = $this->queryGetNotifications($select, false);

            $idArray = $alerts->pluck('id')->toArray();
            // Query to filter out IDs that already exist in user_alert_reads
            $existingIds = UserAlertRead::whereIn('user_alert_id', $idArray)
            ->where('user_id', $userId)
            ->pluck('user_alert_id')
            ->toArray();

            // Filter the $idArray to exclude existing IDs
            $idsToInsert = array_diff($idArray, $existingIds);

            // Prepare the data for insertion
            $insertData = [];

            foreach ($idsToInsert as $id) {
                $insertData[] = [
                    'user_id' => Auth()->user()->id,
                    'user_alert_id' => $id,
                    'read_date' => Carbon::now()->timestamp,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
            
            // Insert the data in a single query
            UserAlertRead::insert($insertData);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    private function queryGetNotifications($select, $isGet)
    {
        $usersAccess = config('const.employee_pm_notifications');
        $user = Auth()->user();

        $query = UserAlert::join('users', function ($join) {
            $join->on('users.id', '=', 'user_alerts.user_id')
                ->whereNull('users.deleted_at');
        })
        ->leftJoin('petitions', function ($join) use ($user, $usersAccess) {
            $join->on('user_alerts.resource_id', '=', 'petitions.id')->where('user_alerts.resource_type', '=', 'Petition');
        })
        ->leftJoin('deadline_modifications', function ($join) use ($user, $usersAccess) {
            $join->on('user_alerts.resource_id', '=', 'deadline_modifications.id')
            ->where('user_alerts.resource_type', '=', 'DeadlineModification');
        })
        ->leftJoin('user_alert_reads as uareads', function ($join) use ($user) {
            $join->on('uareads.user_alert_id', '=', 'user_alerts.id')
            ->where('uareads.user_id', $user->id);
        });
        if ($isGet) {
            $query->leftJoin(DB::raw('users AS resource_users'), function ($join) {
                $join->on('resource_users.id', '=', DB::raw("CASE
                    WHEN user_alerts.resource_type = 'Petition' THEN petitions.user_id
                    WHEN user_alerts.resource_type = 'DeadlineModification' THEN deadline_modifications.user_id
                    ELSE NULL
                END"));
            });
        }

        $alerts = $query->selectRaw($select)
        ->whereBetween('user_alerts.created_at', [
            Carbon::now()->startOfDay(),
            Carbon::now()->endOfDay()
        ])
        ->where(function ($query) use ($user, $usersAccess) {
            if (!in_array($user->id, $usersAccess)) {
                $query->whereIn('user_alerts.action', ['updated', 'deleted'])
                ->where(function ($subquery) use ($user) {
                    $subquery->where(function ($q) use ($user) {
                        $q->where('user_alerts.resource_type', 'Petition')
                            ->where('petitions.user_id', $user->id);
                    })->orWhere(function ($q) use ($user) {
                        $q->where('user_alerts.resource_type', 'DeadlineModification')
                            ->where('deadline_modifications.user_id', $user->id);
                    });
                });
            } else {
                $query->where('user_alerts.user_id', '!=', $user->id);
                if ($user->position == 1) {
                    $query->where('users.department_id', $user->department_id);
                }
            }
            //for HR, they dont need to get notification about deadline modifications
            if ($user->department_id === 7) {
                $query->where('user_alerts.resource_type', '!=', 'DeadlineModification');
            }
        })
        ->orderBy('user_alerts.created_at', 'desc')
        ->get();

        return $alerts;
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
        if (!empty($requestDatas['user_status'])) {
            $addedQuery = $addedQuery->where('user_status', $requestDatas['user_status']);
        }
        if (!empty($requestDatas['user_id'])) {
            $addedQuery = $addedQuery->where('users.id', $requestDatas['user_id']);
        }
        if (!empty($requestDatas['date_official'])) {
            $addedQuery = $addedQuery->whereDate('users.date_official', $requestDatas['date_official']);
        }
        if (!empty($requestDatas['created_at'])) {
            $addedQuery = $addedQuery->whereBetween('users.created_at', [
                Carbon::parse($requestDatas['created_at'])->startOfDay(),
                Carbon::parse($requestDatas['created_at'])->endOfDay(),
            ]);
        }
        if (!empty($requestDatas['department_id'])) {
            $addedQuery = $addedQuery->where('users.department_id', $requestDatas['department_id']);
        }
        if (!empty($requestDatas['user_type'])) {
            $addedQuery = $addedQuery->where('users.type', $requestDatas['user_type']);
        }
        if (!empty($requestDatas['gender'])) {
            $addedQuery = $addedQuery->where('user_personal_infos.gender', $requestDatas['gender']);
        }

        return $addedQuery;
    }

    /** Handle columns value
     *
     * @param $employees
     * @return $newData
    */
    private function handleColumnsValue($employees)
    {
        $newData = array();
        $userLogin = Auth()->user();

        //list permission
        $permissions = config('const.permissions');
        //list departments
        $departments = config('const.departments');
        //list positions
        $positions = config('const.positions');
        //avatar folder path
        $avatarFolder = config('const.avatar_file_folder');
        //phone view
        $employeeIdPhoneView = config('const.employee_id_phone_view');
        $user_type = config('const.user_type');

        foreach ($employees as $employee) {
            $phone = "**********";
            if (in_array($userLogin->id, $employeeIdPhoneView)) {
                $phone = $employee->phone;
            } elseif ($userLogin->position == 1 && $employee->department_id == $userLogin->department_id) {
                $phone = $employee->phone;
            } elseif ($userLogin->id == $employee->id) {
                $phone = $employee->phone;
            } elseif ($employee->id == 63) {
                $phone = $employee->phone;
            }

            //show birthday in current month
            $isBirthday = false;
            if (Carbon::now()->month == Carbon::parse($employee->birthday)->month) {
                $isBirthday = true;
            }

            //Push element onto the newData array
            array_push($newData, [
                'id' => $employee->id,
                'avatar' => $avatarFolder.'/'.$employee->avatar,
                'fullname' => $employee->fullname,
                'phone' => $phone,
                'email' => $employee->email,
                'birthday' => Carbon::create($employee->birthday)->format('d/m/Y'),
                'is_birthday' => $isBirthday,
                'department_id' => isset($departments[$employee->department_id]) ?
                                    $departments[$employee->department_id] : $employee->department_id,
                'position' => isset($positions[$employee->position]) ?
                                    $positions[$employee->position] : $employee->position,
                // 'date_official' => !empty($employee->date_official) ?
                //                     Carbon::create($employee->date_official)->format('d/m/Y') : "Thử việc",
                'date_official' => !empty($employee->date_official) ?
                                    Carbon::create($employee->date_official)->format('d/m/Y') : $this->getDuration($employee),
                'permission' => isset($permissions[$employee->permission]) ?
                                    $permissions[$employee->permission] : $employee->permission,
                'updated_at' => $employee->updated_at,
                'created_at' => Carbon::create($employee->created_at)->format('d/m/Y'),
                'user_status' => $employee->user_status,
                'type' => isset($user_type[$employee->type]) ? $user_type[$employee->type] : $employee->type,
                'gender' => $employee->gender ,
            ]);
        }

        return $newData;
    }

    /** Create an upload file folder
     * return String $path
    */
    public function createUpFileFolders()
    {
        $fileFolder = config('const.avatar_file_folder');
        
        $fileFolderPath = public_path($fileFolder);

        //create file folder
        if (!is_dir($fileFolderPath)) {
            File::makeDirectory($fileFolderPath);
        }

        return $fileFolder;
    }

    public function onBuzz(Request $request)
    {
        try {
            $requestDatas = $request->all();

            // broadcast(new PrivateWebSocket(Auth()->user()->fullname, $requestDatas['userIds']))->toOthers();
            broadcast(new PrivateWebSocket(Auth()->user()->fullname, $requestDatas['userIds'], 'user'))->toOthers();

            return response()->json([
                'success' => __('MSG-S-007'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getDuration($employee)
    {
        $startDate = Carbon::now();
        if ($employee->type == 3) {
            $startDate = $employee->created_at;
        } else if ($employee->type == 2) {
            if ($employee->date_probation) {
                $startDate = $employee->date_probation;
            } else {
                $startDate = $employee->created_at;
            }

        }
        $requestDatas = [
            'start_date' => Carbon::create($startDate)->format('Y/m/d'),
            'end_date' => Carbon::now()->format('Y/m/d'),
            'user_id' => $employee->id,
        ];

        $timeSheets = CommonController::getTotalTimesheetReport($requestDatas);
        
        return isset($timeSheets[0]['origin_workday']) ? number_format($timeSheets[0]['origin_workday'], 2, '.', '').' công' : '';
    }

}
