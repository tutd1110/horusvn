<?php

namespace App\Http\Controllers\api\OrderStore;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\OrderStore\OrderStatistialRequest;
use App\Models\User;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Enums\OrderPaidReportStatus;
use App\Models\OrderUser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * OrderStatistial API
 *
 * @group OrderStatistial
 */
class OrderStatistialController extends Controller
{

    public function statistialOrderWeek(OrderStatistialRequest $request)
    {
        try {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $user_id = $request->user_id;
            $department_id = $request->department_id;
            $orderStatusRequest = $request->status ?? '';
            $user_status = $request->user_status ?? 1;
            $store_id = $request->query('store_id') ?? '';

            $collection = User::with(['orderUser', 'orders' => function ($query) use ($start_date, $end_date) {
                $query->whereDate('created_at', '>=', $start_date)
                        ->whereDate('created_at', '<=', $end_date);
            }]);
            if ($user_id) {
                $collection->where('id', $user_id);
            }
            if($department_id){
                $collection->where('department_id',$department_id);
            }
            if($user_status){
                $collection->where('user_status',$user_status);
            }
            $collection = $collection->orderBy('users.date_official','asc')
                                    ->orderBy('users.created_at','asc');

            // if filter with status -> get all time collection
            if($orderStatusRequest && !$user_id)
            {
                $collection = User::with(['orderUser', 'orders'])
                                    ->orderBy('users.date_official','asc')
                                    ->orderBy('users.created_at','asc');
                if($department_id){
                    $collection = $collection->where('department_id',$department_id);
                }
            }
            $collection = $this->formatCollection($collection->get(), $start_date, $orderStatusRequest, $user_id);
            $countOrderWeek = Order::whereDate('orders.created_at', '>=', $start_date)
                                    ->join('order_stores','orders.store_id','=','order_stores.id')
                                    ->whereDate('orders.created_at', '<=', $end_date)
                                    ->where('order_stores.type','RICE');                  
            if($store_id){
                $countOrderWeek->where('orders.store_id', $store_id);
            }
            $countOrderWeek = $countOrderWeek->count();

            return response()->json(['data' => $collection,'countOrderWeek'=>$countOrderWeek]);
        } catch (Throwable $th) {
            Log::error($th->getMessage());
            throw $th;
        }
    }

    protected function formatCollection($collection, $start_date, $orderStatusRequest, $user_id_rq)
    {
        // fixed order store type default = 'RICE'
        $data = [];
        foreach ($collection as $key => $item) {
            $user_id = $item->id;
            $calculateAmount = $this->calculateTotalAmount($start_date, $item);

            if(!$user_id_rq && $orderStatusRequest && $orderStatusRequest == 'NONE' && $calculateAmount['final_price'] == 0) continue;

            if(!$user_id_rq && $orderStatusRequest && $orderStatusRequest == 'COMPLETED') {
                if($calculateAmount['final_price'] == 0 && ($calculateAmount['total_week_price'])){}
                else continue;
            }

            $orders = [];
            if (!empty($item->orders[0])) {
                foreach ($item->orders as $k => $i) {
                    if($i->total_amount == 0) continue;
                    $dateFormated = \Carbon\Carbon::parse($i->created_at)->format('Ymd');
                    $orders[$dateFormated] = [
                        'id' => $i->id,
                        'total_amount' => $i->total_amount,
                        'admin_note'=>$i->admin_note,
                        'status' => $i->status
                    ];
                }
                $data[$key] = [
                    'id' => $user_id,
                    'fullname' => $item->fullname,
                    'alias_name' => $item->orderUser->alias_name ?? '',
                    'is_collected_debt' => $item->orderUser->is_collected_debt ?? false,
                    'orders' => $orders,
                    'calculate_amounts' => $calculateAmount,
                    'payment_status' => $this->checkStatusPayment($user_id),
                    'prepaid_amount' => $item->orderUser->prepaid_amount ?? 0,
                ];
            } else {
                $data[$key] = [
                    'id' => $user_id,
                    'fullname' => $item->fullname,
                    'alias_name' => $item->orderUser->alias_name ?? '',
                    'is_collected_debt' => $item->orderUser->is_collected_debt ?? false,
                    'orders' => [],
                    'calculate_amounts' => $calculateAmount,
                    'payment_status' => $this->checkStatusPayment($user_id),
                    'prepaid_amount' => $item->orderUser->prepaid_amount ?? 0,
                ];
            }
        }
        return $data;
    }

    public function calculateTotalAmount($start_date, $user)
    {
        // tổng tuần từ ngày
        $newEndDate = new Carbon($start_date);
        $newEndDate = $newEndDate->addDays(7)->format('Y-m-d');

        $totalPrice = Order::selectRaw('sum(total_amount)')->where('user_id', $user->id)
            ->whereBetween('created_at', [$start_date, $newEndDate])
            ->first();

        $remainingPrice = Order::selectRaw('sum(total_amount)')->where('user_id', $user->id)
            ->whereDate('created_at', '<=', $start_date)
            ->where('status', 'PENDING')
            ->first();

        $finalPriceBase = Order::selectRaw('sum(total_amount)')->where('user_id', $user->id)
            ->where('status', 'PENDING')
            ->first();

        //  tiền phạt 10% thêm vào tiền nợ khi chưa thanh toán tuần trước(min là 50k)
        $taxCustom = 0;
        $finalPrice = $finalPriceBase->sum;
        $baseTax = 50000;
        // is_collected_debt = true
        if($user->orderUser && $user->orderUser->is_collected_debt && $remainingPrice && $remainingPrice->sum > 0){
            if($remainingPrice->sum >= $baseTax)
            {
                $taxCustom = (int)$finalPrice * 0.1; // 10%
                $finalPrice += (int)$taxCustom;
            }else{
                $taxCustom = (int)$baseTax; // 50k
                $finalPrice = (int)$totalPrice->sum + $taxCustom;
            }
        }

        return [
            'total_week_price' => $totalPrice->sum ?? 0,
            'remaining_price' => $remainingPrice->sum ?? 0,
            'tax_custom'=>$taxCustom,
            'final_price' => $finalPrice ?? 0,
        ];
    }

    protected function checkStatusPayment($user_id)
    {
        $userOrdered = Order::where('user_id',$user_id)
                                ->exists();
        if(!$userOrdered) return '';

        $orderUser = OrderUser::where('paid_report_status','SENT')
                                ->where('user_id',$user_id)
                                ->first();
        if($orderUser) return 'SENT';

        $orderPendingExists = Order::where('user_id',$user_id)
                                ->where('status','PENDING')
                                ->exists();
        if($orderPendingExists) return 'NONE';

        $orderCompleted = OrderUser::where('paid_report_status','COMPLETED')
                                    ->where('user_id',$user_id)
                                    ->first();
        if($orderCompleted) return 'COMPLETED';
    }
}
