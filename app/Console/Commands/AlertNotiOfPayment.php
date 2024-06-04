<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\SendAlertPaymentEvent;
use Illuminate\Support\Facades\Log;

class AlertNotiOfPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:noti';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Thong bao dong tien an hang tuan';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        logger('=================start cron alert payment==================');
        try{
            $timeNow = \Carbon\Carbon::now()->format('Y-m-d');
            $userOrder = \App\Models\User::whereHas('orders', function($q) use ($timeNow){
                $q->whereDate('orders.created_at','<=',$timeNow)
                    ->where('orders.total_amount','>',0)
                    ->where('orders.status','PENDING')
                    ->whereNull('orders.deleted_at');
            })
            ->with(['orders'=>function($q) use ($timeNow){
                $q->whereDate('orders.created_at','<=',$timeNow)
                    ->where('orders.total_amount','>',0)
                    ->where('orders.status','PENDING')
                    ->whereNull('orders.deleted_at');
            }])->get();
            
            foreach($userOrder as $val){
                try{
                    $totalPriceUser = 0;
                    $userId = $val->id; 

                    foreach($val->orders as $order){
                        $totalPriceUser += (int)$order->total_amount;
                    }
                    // broadcast event
                    broadcast(new SendAlertPaymentEvent($userId,$totalPriceUser));
                }catch(\Throwable $th){
                    Log::error($th->getMessage());
                    continue;
                }
            }
        }catch(\Throwable $th){
            Log::error('Error when run cron alert payment: '.$th->getMessage());
        }
    }
}
