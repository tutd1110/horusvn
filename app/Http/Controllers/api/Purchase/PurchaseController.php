<?php

namespace App\Http\Controllers\api\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Purchase;
use App\Models\PurchaseSupplier;
use App\Models\CompanySupplier;
use App\Models\Project;
use Carbon\Carbon;
use File;
use Storage;

use App\Http\Requests\api\Purchase\PurchaseRegisterRequest;
use App\Http\Requests\api\Purchase\PurchaseUpdateRequest;
use App\Http\Requests\api\Purchase\SupplierRegisterRequest;
use App\Http\Requests\api\Purchase\SupplierUpdateRequest;

/**
 * Project API
 *
 * @group Project
 */
class PurchaseController extends Controller
{

    public function getSelectboxes()
    {
        try {
            $departments = config('const.departments');
            $departments = array_map(function ($value, $label) {
                return ['value' => $value, 'label' => $label];
            }, array_keys($departments), $departments);
            
            $purchase_type = config('const.purchase_type');
            $users = User::select('id', 'fullname')->get();
            $projects = Project::select('id', 'name')->whereNull('projects.deleted_at')->get();
            $company_supplier = CompanySupplier::select('id', 'name')->whereNull('company_suppliers.deleted_at')->get();

            $data = [
                'users' => $users,
                'purchase_type' => $purchase_type,
                'departments' => $departments,
                'projects' => $projects,
                'company_supplier' => $company_supplier,
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

    public function getPurchase(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $query = Purchase::leftJoin('projects', function ($join) {
                $join->on('purchases.project_id', '=', 'projects.id')->whereNull('projects.deleted_at');
            })
            ->select(
                'purchases.id',
                'purchases.name',
                'purchases.user_created',
                'purchases.note',
                'purchases.type',
                'purchases.project_id',
                'projects.name as project_name',
                'purchases.created_at',
                DB::raw('(SELECT COUNT(*) FROM purchase_suppliers WHERE purchase_suppliers.purchase_id = purchases.id) as purchase_supplier_count'),
            )
            ->orderBy('purchases.created_at', 'desc');

            if (isset($requestDatas['project_id']) && !empty($requestDatas['project_id'])) {
                $query->where('purchases.project_id', $requestDatas['project_id']);
            }
            if (isset($requestDatas['type']) && !empty($requestDatas['type'])) {
                $query->where('purchases.type', $requestDatas['type']);
            }
            if (isset($requestDatas['user_created']) && !empty($requestDatas['user_created'])) {
                $query->where('purchases.user_created', $requestDatas['user_created']);
            }

            if (isset($requestDatas['name']) && $requestDatas['name'] != '') {
                $query->where(
                    DB::raw('lower(purchases.name)'),
                    'LIKE',
                    '%'.mb_strtolower(urldecode($requestDatas['name']), 'UTF-8').'%'
                );
            }

            $purchases = $query->get();

            foreach ($purchases as $purchase) {
                $purchaseSuppliers = PurchaseSupplier::leftJoin('company_suppliers', function ($join) {
                        $join->on('company_suppliers.id', '=', 'purchase_suppliers.company_id')->whereNull('company_suppliers.deleted_at');
                    })
                    ->select(
                        'purchase_suppliers.id',
                        'purchase_suppliers.price',
                        'purchase_suppliers.delivery_time',
                        'purchase_suppliers.path',
                        'purchase_suppliers.path_po',
                        'purchase_suppliers.status',
                        'purchase_suppliers.note',
                        'company_suppliers.name',
                        'company_suppliers.phone',
                        'company_suppliers.tax_code',
                        'company_suppliers.address',
                    )
                    ->where('purchase_suppliers.purchase_id', '=', $purchase->id)
                    ->orderBy('purchase_suppliers.created_at', 'desc')
                    ->get();
                $users = User::select('fullname')->where('id',$purchase->user_created)->first();
                $purchase->purchase_suppliers = $purchaseSuppliers;
                $purchase->user_created = $users->fullname;
            }

            //no search results
            if (count($purchases) === 0) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            return response()->json($this->handleColumnsValue($purchases));
            
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getPurchaseById(Request $request)
    {
        try {
            $purchase = Purchase::where('id', $request->id)->first();

            return response()->json($purchase);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(PurchaseRegisterRequest $request)
    {
        try {
            $requestDatas = $request->all();

            DB::beginTransaction();

            $purchase = Purchase::create([
                'name' =>  $requestDatas['name'],
                'note' => $requestDatas['note'] ?? null,
                'project_id' => $requestDatas['project_id'],
                'type' => $requestDatas['type'],
                'user_created' => Auth()->user()->id,
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
    public function update(PurchaseUpdateRequest $request)
    {
        try {
            $requestDatas = $request->all();

            DB::beginTransaction();

            $purchase = Purchase::findOrFail($requestDatas['id']);
            $purchase->name = $requestDatas['name'];
            $purchase->project_id = $requestDatas['project_id'];
            $purchase->type = $requestDatas['type'];
            $purchase->note = $requestDatas['note'];
            $purchase->save();

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
            $purchase = Purchase::findOrFail($requestDatas['id']);
            $purchase->delete();

            $purchaseSupplier = PurchaseSupplier::where('purchase_suppliers.purchase_id', $requestDatas['id'])->get();
            foreach ($purchaseSupplier as $value) {
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
    public function deleteSupplier(Request $request)
    {
        try {
            $requestDatas = $request->all();

            DB::beginTransaction();
            $purchaseSupplier = PurchaseSupplier::findOrFail($requestDatas['id']);
            $purchaseSupplier->delete();

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
    public function getPurchaseSupplierById(Request $request)
    {
        try {
            $purchase = PurchaseSupplier::leftJoin('company_suppliers', function ($join) {
                $join->on('company_suppliers.id', '=', 'purchase_suppliers.company_id')->whereNull('company_suppliers.deleted_at');
            })
            ->select(
                'purchase_suppliers.*',
                'company_suppliers.name',
                'company_suppliers.phone',
                'company_suppliers.tax_code',
                'company_suppliers.address',
            )
            ->where('purchase_suppliers.id', $request->id)->first();

            return response()->json($purchase);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getCompanySupplier(Request $request)
    {
        try {
            $requestDatas = $request->all();
            $company = CompanySupplier::select(
                'id',
                'phone',
                'tax_code',
                'address',
            );
            if (isset($requestDatas['id'])) {
                $company->where('company_suppliers.id', $requestDatas['id']);
            }
            if (isset($requestDatas['tax_code'])) {
                $company->where('company_suppliers.tax_code', $requestDatas['tax_code']);
            }
            $company = $company->first();

            return response()->json($company);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function storeSupplier(SupplierRegisterRequest $request)
    {
        try {
            $requestDatas = $request->all();

            DB::beginTransaction();
            if ($requestDatas['newCompany'] == 'true') {
                $companySupplier = CompanySupplier::create([
                    'name' => $requestDatas['name'],
                    'phone' => $requestDatas['phone'],
                    'tax_code' => $requestDatas['tax_code'],
                    'address' => $requestDatas['address'],
                    'user_created' => Auth()->user()->id,
                ]);
            } else if ( $requestDatas['newCompany'] == 'false' ) {
                $companySupplier = CompanySupplier::findOrFail($requestDatas['company_id']);

                $companySupplier->phone = $requestDatas['phone'];
                $companySupplier->tax_code = $requestDatas['tax_code'];
                $companySupplier->address = $requestDatas['address'];

                $companySupplier->save();
            }

            $purchaseSupplier = PurchaseSupplier::create([
                'purchase_id' =>  $requestDatas['purchase_id'],
                'company_id' =>  $companySupplier['id'],
                'delivery_time' =>  $requestDatas['delivery_time'],
                'price' =>  $requestDatas['price'],
                'note' => $requestDatas['note'] ?? null,
                'user_created' => Auth()->user()->id,
                'path' => $request->has('file') ?  $this->saveFile($requestDatas['file'], $requestDatas['purchase_id']) : '',
                'path_po' => $request->has('filePO') ? $this->saveFile($requestDatas['filePO'], $requestDatas['purchase_id']) : '',
                'status' => 0,
            ]);

            DB::commit();

            return response()->json([
                'success' => __('MSG-D-003'),
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
    public function updateSupplier(SupplierUpdateRequest $request)
    {
        try {
            $requestDatas = $request->all();
            DB::beginTransaction();
            if ($requestDatas['newCompany'] == 'true') {
                $companySupplier = CompanySupplier::create([
                    'name' =>  $requestDatas['name'],
                    'phone' =>  $requestDatas['phone'],
                    'tax_code' =>  $requestDatas['tax_code'],
                    'address' =>  $requestDatas['address'],
                    'user_created' => Auth()->user()->id,
                ]);
            } else if ( $requestDatas['newCompany'] == 'false' ) {
                $companySupplier = CompanySupplier::findOrFail($requestDatas['company_id']);

                $companySupplier->phone = $requestDatas['phone'];
                $companySupplier->tax_code = $requestDatas['tax_code'];
                $companySupplier->address = $requestDatas['address'];

                $companySupplier->save();
            }

            $purchaseSupplier = PurchaseSupplier::findOrFail($requestDatas['id']);
            
            $purchaseSupplier->purchase_id = $requestDatas['purchase_id'];
            $purchaseSupplier->company_id = $companySupplier['id'];
            $purchaseSupplier->delivery_time = $requestDatas['delivery_time'];
            $purchaseSupplier->price = $requestDatas['price'];
            $purchaseSupplier->note = $requestDatas['note'];
            $purchaseSupplier->user_created = Auth()->user()->id;
            $request->has('file') ? $purchaseSupplier->path = $this->saveFile($requestDatas['file'], $requestDatas['purchase_id']) : '';
            $request->has('filePO') ? $purchaseSupplier->path_po =  $this->saveFile($requestDatas['filePO'], $requestDatas['purchase_id']) : '';

            $purchaseSupplier->save();
            DB::commit();

            return response()->json([
                'success' => __('MSG-D-005'),
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
    public function updateSupplierStatus(Request $request)
    {
        try {
            $requestDatas = $request->all();
            DB::beginTransaction();
            $purchaseSupplier = PurchaseSupplier::findOrFail($requestDatas['id']);
            
            $purchaseSupplier->status = 1;

            $purchaseSupplier->save();
            DB::commit();

            return response()->json([
                'success' => __('MSG-D-005'),
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

    public function createUpFileFolders($purchaseId)
    {
        $fileFolder = 'purchases';
        
        $fileFolderPath = public_path($fileFolder.'/'.$purchaseId);
        // dd($fileFolderPath);
        //create file folder
        File::ensureDirectoryExists(dirname($fileFolderPath));
        if (!is_dir($fileFolderPath)) {
            File::makeDirectory($fileFolderPath);
        }

        return $fileFolder;
    }

    private function saveFile($file, $purchaseSupplierId)
    {
        $fileFolder = $this->createUpFileFolders($purchaseSupplierId);
        // $fileName = uniqid() . '_' . $file->getClientOriginalName();
        $fileName = uniqid() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
        $fileContent = file_get_contents($file);
        $path = public_path($fileFolder.'/'.$purchaseSupplierId.'/'.$fileName);
        File::put($path, $fileContent);

        $url = $fileFolder.'/'.$purchaseSupplierId.'/'.$fileName;
        return $url;
    }

    private function handleColumnsValue($purchases)
    {
        $newData = array();
        $typeMapping = config('const.purchase_type');

        foreach ($purchases as $key => $purchase) {
            $typeInfo = collect($typeMapping)->where('value', $purchase->type)->first();
            $label = $typeInfo ? $typeInfo['label'] : $purchase->type;
            $newData[] = array_merge(
                $purchase->toArray(),
                ['type' => $label]
            );
        }
        return $newData;
    }
}
