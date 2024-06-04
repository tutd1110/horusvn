<?php

namespace App\Http\Controllers\api\PartnerConfig;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Repositories\HanetRepository;
use App\Models\PartnerConfig;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\api\PartnerConfig\PartnerConfigDeleteRequest;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

/**
 * PartnerConfig API
 *
 * @group PartnerConfig
 */
class PartnerConfigController extends Controller
{
    /**
     * @var HanetRepository
     */
    private $hanetRepository;

    public function __construct(HanetRepository $hanetRepository)
    {
        $this->hanetRepository = $hanetRepository;
    }

    /** PartnerConfig list
     *
     *
     * @group PartnerConfig
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
    public function getPartnerConfig()
    {
        try {
            //Role check
            if (Auth()->user()->permission != "1") {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-017')
                ], Response::HTTP_NOT_FOUND);
            }

            $query = PartnerConfig::query()
                ->select('id', 'setting')
                ->where('code', 'HANET');

            $partner = $query->first();

            $data = [];
            if ($partner) {
                $setting = json_decode($partner->setting);

                $data = [
                    'id' => $partner->id,
                    'client_id' => $setting->client_id,
                    'client_secret' => $setting->client_secret
                ];
            }

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPlaces()
    {
        try {
            //Role check
            if (Auth()->user()->permission != "1") {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
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

            $result = $this->hanetRepository->getPlaces($setting->access_token);

            if ($result->returnCode != 1) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-018')
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json($result->data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function registerEmployee(Request $request)
    {
        set_time_limit(300);

        try {
            //Role check
            if (Auth()->user()->permission != "1") {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-017')
                ], Response::HTTP_NOT_FOUND);
            }

            $employee = User::findOrFail($request->employee_id);
            //Employee not exists
            if (!$employee) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
            }

            $partner = PartnerConfig::query()->select('id', 'setting')->where('code', 'HANET')->first();
            //Employee not exists
            if (!$partner) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
            }
            $setting = json_decode($partner->setting);

            $result = $this->hanetRepository->registerEmployee($setting->access_token, $request->place_id, $employee);

            if ($result->returnCode != 1) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-018')
                ], Response::HTTP_NOT_FOUND);
            }

            $data = $result->data;
            //start transaction
            DB::beginTransaction();

            $employee->place_id = $request->place_id;
            $employee->place_name = $request->place_name;
            $employee->face_image_url = $data->file;
            $employee->user_code = $data->aliasID;

            $employee->save();

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
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

    public function updateEmployeeFaceID(Request $request)
    {
        set_time_limit(300);

        try {
            //Role check
            if (Auth()->user()->permission != "1") {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-017')
                ], Response::HTTP_NOT_FOUND);
            }

            $employee = User::findOrFail($request->employee_id);
            //Employee not exists
            if (!$employee) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
            }

            $partner = PartnerConfig::query()->select('id', 'setting')->where('code', 'HANET')->first();
            //Employee not exists
            if (!$partner) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
            }
            $setting = json_decode($partner->setting);

            $result = $this->hanetRepository->updateEmployeeFaceIDUrlByAliasID(
                $setting->access_token,
                $request->place_id,
                $employee
            );

            if ($result->returnCode != 1) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-018')
                ], Response::HTTP_NOT_FOUND);
            }

            $data = $result->data;
            //start transaction
            DB::beginTransaction();

            $employee->face_image_url = $data->path;

            $employee->save();

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
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

    public function syncEmployees()
    {
        try {
            //Role check
            if (Auth()->user()->permission != "1") {
                return response()->json([
                    'status' => Response::HTTP_FORBIDDEN,
                    'errors' => '',
                ], Response::HTTP_FORBIDDEN);
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

                $places = $this->hanetRepository->getPlaces($accessToken);
                if ($places->returnCode == 1) {
                    foreach ($places->data as $value) {
                        $employees = $this->hanetRepository->getAllUsers($accessToken, $value->id);
                        
                        if ($employees->returnCode == 1) {
                            foreach ($employees->data as $employee) {
                                $exist = User::query()->where('user_code', $employee->aliasID)->exists();

                                if (!$exist) {
                                    User::create([
                                        'fullname' => $employee->name,
                                        'email' => Str::random(10).'@horusvn.com',
                                        'password' => Hash::make('Chamchi123'),
                                        'avatar' => 'employee.png',
                                        'phone' => '0999123123',
                                        'birthday' => Carbon::create(Carbon::now())->format('Y/m/d'),
                                        'department_id' => 2,
                                        'position' => 0,
                                        'permission' => 0,
                                        'check_type' => 1,
                                        'place_id' => $employee->placeID,
                                        'face_image_url' => $employee->avatar,
                                        'user_code' => $employee->aliasID
                                    ]);
                                }
                            }
                            DB::commit();

                            return response()->json([
                                'success' => __('MSG-S-001'),
                            ], Response::HTTP_OK);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Delete Partner Config
     *
     * @group Partner Config
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
     *    "errors": "Mã thiết bị không hợp lệ",
     *    "errors_list": {
     *          "id": [
     *              "Mã thiết bị không hợp lệ"
     *          ]
     *      }
     * }
     *
     * @response 500 {
     *    'status' : 500,
     *    'errors' : 'XXXXXXXX'
     * }
    */
    public function destroy(PartnerConfigDeleteRequest $request)
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

            $partner = PartnerConfig::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            //delete partner
            $partner->delete();

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
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
