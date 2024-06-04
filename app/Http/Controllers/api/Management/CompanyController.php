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
use App\Models\Company;
use App\Models\Department;
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
class CompanyController extends Controller
{
    public function getCompany(Request $request)
    {
        try {
            $requestDatas = $request->all();
            $query = Company::select(
                'companies.id',
                'companies.name',
                'companies.note',
                'companies.db_connection',
                'companies.date_established',
                'companies.tax_code',
            );
            if (isset($requestDatas['name']) && $requestDatas['name'] != '') {
                $query->where(
                    DB::raw('lower(companies.name)'),
                    'LIKE',
                    '%'.mb_strtolower(urldecode($requestDatas['name']), 'UTF-8').'%'
                );
            }

            $data = $query->orderBy('companies.id', 'asc')->get();

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
    public function getCompanyById(Request $request)
    {
        try {
            $data = Company::where('id', $request->id)->first();

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
            $company = Company::create([
                'name' =>  $requestDatas['name'],
                'note' => $requestDatas['note'] ?? null,
                'db_connection' => $requestDatas['db_connection'] ?? null,
                'date_established' => $requestDatas['date_established'] ?? null,
                'tax_code' => $requestDatas['tax_code'] ?? null,
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

            $company = Company::findOrFail($requestDatas['id']);
            $company->name = $requestDatas['name'];
            $company->db_connection = $requestDatas['db_connection'];
            $company->note = $requestDatas['note'];
            $company->db_connection = $requestDatas['db_connection'];
            $company->date_established = $requestDatas['date_established'];
            $company->tax_code = $requestDatas['tax_code'];
            $company->save();

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
    public function delete(Request $request)
    {
        try {
            $requestDatas = $request->all();

            DB::beginTransaction();
            $company = Company::findOrFail($requestDatas['id']);
            $company->delete();

            $departmentCompany = Department::where('departments.company_id', $requestDatas['id'])->get();
            foreach ($departmentCompany as $value) {
                $value->delete();
            }

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
