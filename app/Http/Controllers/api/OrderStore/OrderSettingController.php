<?php

namespace App\Http\Controllers\api\OrderStore;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\OrderStore\SaveOrderSettingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use \Symfony\Component\HttpFoundation\Response;
use App\Models\OrderSetting;
use App\Models\OrderStoreMenu;
use Illuminate\Support\Facades\DB;

/**
 * OrderStoreSetting API
 *
 * @group OrderStoreSetting
 */
class OrderSettingController extends Controller
{
    public function show()
    {
        try {
            $store = OrderSetting::with(['store'=>function($query){
                $query->select('id','name','type');
            }])->first();

            return response()->json(['setting' => $store]);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => __('MSG-E-003')
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function save(SaveOrderSettingRequest $request)
    {
        try {
            DB::beginTransaction();
           
            $model = new OrderSetting();
            if($request->id){
                $model = OrderSetting::find($request->id);
            }
            // $model->fill($request->only(['store_id','time_alert','content_alert','is_active']));
            $model->fill($request->all());
            $model->save();

            DB::commit();
            return response()->json(['store' => $model, 'message' => "Cấu hình thành công"]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
            throw $th;
        }
    }

    public function getCurrentSetting(){
        try {
            $employeeOrderManageRole = config('const.super_admin');
            $setting = OrderSetting::with(['store'=>function($q){
                $q->select('id','name','max_item','price','location');
                $q->with(['menu']);
            }])->first();

            return response()->json([
                'setting' => $setting,
                "is_administrator" => in_array(Auth()->user()->id, $employeeOrderManageRole) || Auth()->user()->id == 90 ? true : false
            ]);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => __('MSG-E-003')
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
