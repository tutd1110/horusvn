<?php

namespace App\Http\Controllers\api\Management;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Department;
use App\Models\Company;
use Carbon\Carbon;

// use App\Http\Requests\api\Department\DepartmentRegisterRequest;
// use App\Http\Requests\api\Department\DepartmentUpdateRequest;
// use App\Http\Requests\api\Department\SupplierRegisterRequest;
// use App\Http\Requests\api\Department\SupplierUpdateRequest;

/**
 * Project API
 *
 * @group Project
 */
class DepartmentController extends Controller
{
    public function getSelectboxes()
    {
        try {
            $companies = Company::select('id', 'name')->whereNull('companies.deleted_at')->get();

            $data = [
                'companies' => $companies,
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

    public function getDepartment(Request $request)
    {
        try {
            $requestDatas = $request->all();
            $query = Department::select(
                'departments.id',
                'departments.name',
                'departments.note',
                'departments.company_id',
                'departments.short_name',
                'departments.active_job',
                'companies.name as company_name'
            )
            ->join('companies', function ($join) {
                $join->on('departments.company_id', '=', 'companies.id')->whereNull('companies.deleted_at');
            })
            ->whereNull('departments.deleted_at');
            if (isset($requestDatas['name']) && $requestDatas['name'] != '') {
                $query->where(
                    DB::raw('lower(companies.name)'),
                    'LIKE',
                    '%'.mb_strtolower(urldecode($requestDatas['name']), 'UTF-8').'%'
                );
            }
            if (isset($requestDatas['company_id']) && $requestDatas['company_id'] != '') {
                $query->where('departments.company_id', $requestDatas['company_id']);
            }

            $data = $query->orderBy('departments.id', 'asc')->get();

            //no search results
            if (count($data) === 0) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
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
    public function getDepartmentById(Request $request)
    {
        try {
            $data = Department::where('id', $request->id)->first();

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $requestDatas = $request->all();

            DB::beginTransaction();
            $department = Department::create([
                'name' =>  $requestDatas['name'],
                'company_id' => $requestDatas['company_id'] ?? null,
                'short_name' => $requestDatas['short_name'] ?? null,
                'active_job' => $requestDatas['active_job'] ?? null,
                'note' => $requestDatas['note'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'success' => __('MSG-D-002'),
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
    public function update(Request $request)
    {
        try {
            $requestDatas = $request->all();

            DB::beginTransaction();

            $department = Department::findOrFail($requestDatas['id']);
            $department->name = $requestDatas['name'];
            $department->company_id = $requestDatas['company_id'];
            $department->short_name = $requestDatas['short_name'];
            $department->active_job = $requestDatas['active_job'];
            $department->note = $requestDatas['note'];
            $department->save();

            DB::commit();

            return response()->json([
                'success' => __('MSG-D-004'),
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
    public function quickUpdate(Request $request)
    {
        try {
            $requestDatas = $request->all();
            $department = Department::findOrFail($requestDatas['id']);

            if (array_key_exists('active_job', $requestDatas)) {
                $department->active_job = $requestDatas['active_job'];
            }
            $department->save();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function delete(Request $request)
    {
        try {
            $requestDatas = $request->all();

            DB::beginTransaction();
            $department = Department::findOrFail($requestDatas['id']);
            $department->delete();

            DB::commit();
            return response()->json([
                'success' => __('MSG-D-004'),
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
   
}
