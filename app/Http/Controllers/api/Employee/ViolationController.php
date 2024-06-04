<?php

namespace App\Http\Controllers\api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api\CommonController;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationFile;
use App\Http\Requests\api\Violation\ViolationRegisterRequest;
use App\Http\Requests\api\Violation\ViolationEditRequest;
use Carbon\Carbon;
use File;

/**
 * Employee Violation API
 *
 * @group Employee Violation
 */
class ViolationController extends Controller
{
    public function getType()
    {
        $types = config('const.violation_type');

        return response()->json($types);
    }

    public function getViolationsByEmployeeId(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $violations = Violation::with(['files' => function ($query) {
                $query->select('id', 'path', 'violation_id');
            }])
            ->join('users', 'users.id', '=', 'violations.user_id')
            ->select('violations.id', 'users.fullname', 'violations.description', 'violations.time', 'violations.type')
            ->where('user_id', $requestDatas['employee_id'])
            ->when(isset($requestDatas['id']), function ($query) use ($requestDatas) {
                $query->where('violations.id', $requestDatas['id']);
            })
            ->get();

            return response()->json($violations);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getViolationsById(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $violation = Violation::with(['files' => function ($query) {
                $query->select('id', 'path', 'violation_id');
            }])
            ->join('users', 'users.id', '=', 'violations.user_id')
            ->select('violations.id', 'users.fullname', 'violations.description', 'violations.time', 'violations.type')
            ->when(isset($requestDatas['id']), function ($query) use ($requestDatas) {
                $query->where('violations.id', $requestDatas['id']);
            })
            ->first();

            return response()->json($violation);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function store(ViolationRegisterRequest $request)
    {
        try {
            $requestDatas = $request->all();
            //start transaction
            DB::beginTransaction();
            //insert employee
            $violation = Violation::create([
                'user_id' => $requestDatas['employee_id'],
                'description' => $requestDatas['description'] ?? null,
                'type' => $requestDatas['type'],
                'time' => $requestDatas['time']
            ]);

            DB::commit();

            if (!$violation) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }
            
            return response()->json([
                'success' => __('MSG-S-005'),
                'id' => $violation->id
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function uploadImage(Request $request)
    {
        $requestDatas = $request->all();

        try {
            //start transaction
            DB::beginTransaction();

            if ($requestDatas['mode'] === 'update') {
                $files = ViolationFile::where('violation_id', $requestDatas['violation_id'])
                    ->when(
                        isset($requestDatas['success_files']) && count($requestDatas['success_files']) > 0,
                        function ($query) use ($requestDatas) {
                            $query->whereNotIn('id', $requestDatas['success_files']);
                        }
                    )
                    ->get();

                foreach ($files as $item) {
                    //delete old file
                    if (File::exists(public_path($item->path))) {
                        File::delete(public_path($item->path));
                    }

                    //delete post_files record
                    $item->delete();
                }
            }
            
            if (isset($requestDatas['ready_files']) && count($requestDatas['ready_files']) > 0) {
                //create an upload file folder
                $fileFolder = config('const.violation_file_folder');
                CommonController::createUpFileFolders($requestDatas['violation_id'], $fileFolder);

                foreach ($requestDatas['ready_files'] as $file) {
                    $fileName = $file->getClientOriginalName();
                    $fileContent = file_get_contents($file);

                    $path = public_path($fileFolder.'/'.$requestDatas['violation_id'].'/'.$fileName);

                    File::put($path, $fileContent);

                    ViolationFile::create([
                        'violation_id' => $requestDatas['violation_id'],
                        'path' => $fileFolder.'/'.$requestDatas['violation_id'].'/'.$fileName
                    ]);
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(ViolationEditRequest $request)
    {
        try {
            //On request
            $requestDatas = $request->all();

            $violation = Violation::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            $violation->type = $requestDatas['type'];
            $violation->time = $requestDatas['time'];
            $violation->description = $requestDatas['description'];

            $violation->save();

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $fileFolder = config('const.violation_file_folder');

            //on reuqest
            $requestDatas = $request->all();

            $violation = Violation::findOrFail($requestDatas['id']);

            //start transaction
            DB::beginTransaction();

            //delete post
            $violation->delete();

            $folderPath = public_path($fileFolder.'/'.$requestDatas['id']);
            if (is_dir($folderPath)) {
                File::deleteDirectory($folderPath);
            }

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
