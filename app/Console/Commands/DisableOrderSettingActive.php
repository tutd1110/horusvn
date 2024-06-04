<?php

namespace App\Console\Commands;

use App\Models\OrderSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DisableOrderSettingActive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orderSetting:disable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try{
            $orderSetting = OrderSetting::first();
            $endTimeSetting = Carbon::parse($orderSetting->end_time);
            $mergeTime = Carbon::now()->setTime($endTimeSetting->hour,$endTimeSetting->minute,$endTimeSetting->second);

            if(Carbon::now()->gte($mergeTime) && $orderSetting->is_active){
                $orderSetting->is_active = false;
                $orderSetting->save();
            }
        }catch(\Throwable $th){
            Log::error('Error when cron update is_active order setting: '.$th->getMessage());
        }
    }
}
