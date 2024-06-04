<?php

namespace App\Http\Controllers\api\OrderStore;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\OrderStore\FilterListOrderRequest;
use App\Http\Requests\api\OrderStore\QuickUpdateOrderUserRequest;
use App\Http\Requests\api\OrderStore\SaveOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use \Symfony\Component\HttpFoundation\Response;
use App\Models\Order;
use App\Models\OrderSetting;
use App\Models\OrderUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Enums\OrderPaidReportStatus;
use App\Http\Requests\api\OrderStore\QuickUpdateOrderRequest;
use App\Models\OrderStore;
use Throwable;

/**
 * OrderStore API
 *
 * @group OrderStore
 */
class OrderController extends Controller
{

    public function getListOrder(FilterListOrderRequest $request)
    {
        try {
            $date_order = $request->query('date_order') ?? '';
            $user_id = $request->query('user_id') ?? '';
            $department_id = $request->query('department_id') ?? '';
            $user_status = $request->query('user_status') ?? 1;
            $order_status = $request->query('order_status') ?? '';
            $store_type = $request->query('store_type') ?? 'RICE';
            $store_id = $request->query('store_id') ?? '';

            $collection = User::with(['orderUser','orders' => function ($query) use ($date_order) {
                $whereDate = \Carbon\Carbon::now();
                if ($date_order) {
                    $whereDate = $date_order;
                }
                $query->select('id','user_id','store_id','items','status','total_amount','note','created_at','updated_at');
                $query->whereDate('orders.created_at', $whereDate);
                $query->with(['store'=>function($qr){
                    $qr->select('id','type','price','max_item');
                }]);
            }]);

            // filter
            if($order_status == 1){
                $collection->whereHas('orders',function ($query) use ($date_order, $store_type, $store_id) {
                    $whereDate = \Carbon\Carbon::now();
                    if ($date_order) {
                        $whereDate = $date_order;
                    }
                    $query->select('id','user_id','store_id','items','status','total_amount','note','created_at','updated_at');
                    $query->whereDate('orders.created_at', $whereDate);
                    if( isset($store_id) && $store_id != ''){
                        $query->where('orders.store_id', $store_id);
                    }
                    $query->with(['store'=>function($qr){
                        $qr->select('id','type','price','max_item');
                    }]);
                    $query->whereHas('store', function($qr) use($store_type){
                        $qr->where('type',$store_type);
                    });
                });
            }
            if($order_status == 2){
                $collection->whereDoesntHave('orders', function ($query) use ($date_order, $store_type) {
                    $whereDate = \Carbon\Carbon::now();
                    if ($date_order) {
                        $whereDate = $date_order;
                    }
                    $query->select('id','user_id','store_id','items','status','total_amount','note','created_at','updated_at');
                    $query->whereDate('orders.created_at', $whereDate);
                    $query->with(['store'=>function($qr){
                        $qr->select('id','type','price','max_item');
                    }]);
                    $query->whereHas('store', function($qr) use($store_type){
                        $qr->where('type',$store_type);
                    });
                });
            }
            if ($user_id) {
                $collection->where('users.id', $user_id);
            }
            if($department_id){
                $collection->where('users.department_id',$department_id);
            }

            $collection = $collection
                        ->where('users.user_status',$user_status)
                        ->orderBy('users.date_official','asc')
                        ->orderBy('users.created_at','asc')->get();

            $data = [
                "order" => $this->handleColumnsValue($collection),
                "is_administrator" => $this->checkIsAdmin()
            ];

            return response()->json(['users' => $data]);
        } catch (\Throwable $th) {
            return response()->json(['message'=>$th->getMessage()]);

            Log::error($th->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => __('MSG-E-003')
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function show($id)
    {
        try{
            $order = Order::with(['user'=> function ($query){
                        $query->select('id','fullname');
                    }])
                    ->where('id',$id)->first();

            return response()->json(['order'=>$order]);
        }catch(\Throwable $th){
            Log::error($th->getMessage());
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'errors' => __('MSG-E-003')
            ], Response::HTTP_NOT_FOUND);
        }
    }

    public function store(SaveOrderRequest $request)
    {
        try {
            $storeId = $request->store_id;
            $createdAtDateRequest = $request->created_at ?? \Carbon\Carbon::now()->format('Y-m-d');
            $timeNow = \Carbon\Carbon::parse(\Carbon\Carbon::now())->toTimeString();
            $createdAtDate = date('Y-m-d H:i:s', strtotime("$createdAtDateRequest $timeNow"));
            $userId = $request->user_id ?? auth()->id();
            $orderSettingActive = OrderSetting::with('store')->first();
            $startOfCurrentWeek = Carbon::now()->startOfWeek()->format('Y-m-d');

            $storeType = OrderStore::find($storeId) ? OrderStore::find($storeId)->type : 'RICE';

            $userOrders  = Order::where('user_id', $userId)
                ->with(['store'=> function ($q){
                    $q->select('id','name','type');
                }])
                ->whereDate('created_at',"=", $createdAtDate)
                ->whereNull('deleted_at')
                ->get();

            $isTimeSettingAvailable = OrderSetting::whereTime('start_time', '<=', $timeNow)
                ->whereTime('end_time', '>=', $timeNow)
                ->first();

            $remainingPrice = Order::selectRaw('sum(total_amount)')->where('user_id', $userId)
                ->whereDate('created_at', '<', $startOfCurrentWeek)
                ->where('status', 'PENDING')
                ->first();

            if (!$orderSettingActive->is_active && !$this->checkIsAdmin()) {
                return response()->json(["success" => false, 'message' => "Hiện chưa cho phép đặt món.",'disabled_order'=>true]);
            }
            if (!$isTimeSettingAvailable && !$this->checkIsAdmin()) {
                return response()->json(["success" => false, 'message' => "Chưa đến thời gian đặt món hoặc đã hết thời gian."]);
            }
            // if debt store RICE
            if ($remainingPrice && $remainingPrice->sum > 0 && !$this->checkIsAdmin() && $storeType == 'RICE') {
                return response()->json(["success" => false, 'message' => "Bạn còn số nợ chưa thanh toán. Vui lòng thanh toán để tiếp tục đặt món."]);
            }
            if ($this->checkOrdered($userOrders, $orderSettingActive)) { // check with store_id->store_type
                return response()->json(["success" => false, 'message' => "Bạn đã đặt món ngày hôm nay, liên hệ admin để có thể thay đổi"]);
            }

            DB::beginTransaction();
            $model = new Order();
            $model->fill($request->all());
            $model->status = 'PENDING';
            $model->user_id = $userId;
            $model->created_at = $createdAtDate;
            $model->updated_at = $createdAtDate;
            $model->save();

            DB::commit();
            return response()->json([
                'order' => $model,
                "success" => true,
                'message' => "Đặt món thành công"
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getMessage());
            throw $th;
        }
    }

    // check limit order
    private function checkOrdered($userOrders, $orderSettingActive)
    {
        if(!count($userOrders)) return false;

        $flag = false; // not ordered

        foreach($userOrders as $order){
            if($orderSettingActive->store->type == $order->store->type){
                $flag = true; // is ordered with current setting store type
            }
        }

        return $flag;
    }

    public function update(SaveOrderRequest $request, $id)
    {
        try{
            DB::beginTransaction();
            $order = Order::where('id',$id)
                            ->where('user_id',$request->user_id)->first();
            $order->fill($request->all());
            $order->updated_by = auth()->id();
            $order->save();
            DB::commit();

            return response()->json([
                'order' => $order,
                "success" => true,
                'message' => "Cập nhật thành công"
            ]);
        }catch(\Throwable $th){
            DB::rollBack();
            Log::error($th->getMessage());
            throw $th;
        }
    }

    /** Handle columns value
     *
     * @param $employees
     * @return $newData
    */
    private function handleColumnsValue($employees)
    {
        $newData = array();
        $store_type = request()->query('store_type') ?? 'RICE';

        //list permission
        $permissions = config('const.permissions');
        //list departments
        $departments = config('const.departments');
        //avatar folder path
        $avatarFolder = config('const.avatar_file_folder');
        $user_type = config('const.user_type');

        foreach ($employees as $employee) {
            //Push element onto the newData array
            array_push($newData, [
                'id' => $employee->id,
                'avatar' => $avatarFolder.'/'.$employee->avatar,
                'fullname' => $employee->fullname,
                'alias_name' => $employee->orderUser->alias_name ?? '',
                'email' => $employee->email,
                'department_id' => isset($departments[$employee->department_id]) ?
                                    $departments[$employee->department_id] : $employee->department_id,
                'date_official' => !empty($employee->date_official) ?
                                    Carbon::create($employee->date_official)->format('d/m/Y') : "Thử việc",
                'permission' => isset($permissions[$employee->permission]) ?
                                    $permissions[$employee->permission] : $employee->permission,
                'user_status' => $employee->user_status,
                'type' => isset($user_type[$employee->type]) ? $user_type[$employee->type] : $employee->type,
                'orders' => $this->mappingOrders($employee->orders),
            ]);
        }

        return $newData;
    }

    private function mappingOrders($orders){
        $resp = [];
        $store_type = request()->query('store_type') ?? 'RICE';
        foreach($orders as $order){
            if($order->store->type == $store_type) $resp[] = $order;
        }
        return $resp;
    }

    public function quickUpdateUserAlias(QuickUpdateOrderUserRequest $request)
    {
        try {
            $requestDatas = $request->all();

            DB::beginTransaction();
            $user_id = $request->user_id ?? auth()->id();
            foreach($requestDatas as $key => $val){
                OrderUser::updateOrCreate([
                    'user_id' => $user_id
                ],[
                    $key=>$val
                ]);

                // if update paid completed
                if($key == 'paid_report_status' && $val == OrderPaidReportStatus::COMPLETED->value){
                    Order::where('user_id',$user_id)->update([
                        'status' => 'COMPLETED'
                    ]);
                    $orderUser = OrderUser::where('user_id',$user_id)->first();
                    $prepaid_amount = (int)$orderUser->prepaid_amount ?? 0;
                    $total_paid_amount = $request->total_paid_amount;
                    
                    if($orderUser){
                        $orderUser->is_collected_debt = false;
                    }

                    if($prepaid_amount){
                        $orderUser->prepaid_amount = $prepaid_amount + (-(int)$total_paid_amount) >= 0 ? $prepaid_amount + (-(int)$total_paid_amount) : 0;
                    }
                   
                    $orderUser->save();
                }
            }

            DB::commit();
            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    // fail
    public function checkUserIsPaidPayment()
    {
        try{
            $status = Order::where('user_id',auth()->id())
                            ->where('status','!=','COMPLETED')
                            ->exists();
            $statusSent = OrderUser::where('user_id',auth()->id())
                            ->where('paid_report_status', 'SENT')->exists();

            return response()->json(['is_not_paid'=>$status && !$statusSent ? true : false]);
        }catch(Throwable $th){
            Log::error($th);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $th->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }

    private function checkIsAdmin()
    {
        $roleDefine = config('const.super_admin');
        return in_array(auth()->id(), $roleDefine) ? true : false;
    }

    public function quickUpdate(QuickUpdateOrderRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();
            $order = Order::findOrFail($requestDatas['id']);

            //insert order
            if (array_key_exists('admin_note', $requestDatas)) {
                $order->admin_note = $requestDatas['admin_note'];
            }
            if (array_key_exists('total_amount', $requestDatas)) {
                $order->total_amount = $requestDatas['total_amount'];
            }

            $order->save();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function quickUpdateOrderUser(QuickUpdateOrderUserRequest $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            foreach($requestDatas as $key=>$val){
                OrderUser::updateOrCreate([
                    'user_id' => $requestDatas['user_id']
                ],[
                    $key => $val
                ]);
            }

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Throwable $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try{
            $timeNow = \Carbon\Carbon::parse(\Carbon\Carbon::now())->toTimeString();
            $isTimeSettingAvailable = OrderSetting::whereTime('start_time', '<=', $timeNow)
                ->whereTime('end_time', '>=', $timeNow)
                ->first();

            if($isTimeSettingAvailable || $this->checkIsAdmin()){
                Order::findOrFail($id)->delete();
                return response()->json([
                    'success' => 'Hủy đặt món thành công',
                ], Response::HTTP_OK);
            }
            return response()->json([
                'error' => 'Hủy đặt món thất bại vì đã hết thời gian đặt món',
            ], Response::HTTP_OK);
        }catch(\Throwable $th){
            Log::error($th);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                'errors' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
