<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Petition;
use App\Models\User;
use App\Models\UserCheckout;
use App\Models\TimesheetDetail;
use Exception;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SaveLogCheckOut extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'save_log_check_out:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save log checkout before 17:30PM with condition';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $petitions = Petition::leftJoin('users', 'users.id', '=', 'petitions.user_id')
                                ->select(
                                    'petitions.id as id',
                                    'petitions.user_id as user_id',
                                    'users.user_code as user_code',
                                    'petitions.type as type',
                                    'petitions.type_off as type_off',
                                    'petitions.start_date as start_date',
                                    'petitions.status as status'
                                )
                                ->whereDate('petitions.start_date', Carbon::yesterday()->format('Y-m-d'))
                                ->where('petitions.status', 1)
                                ->where(function ($query) {
                                    $query->where('petitions.type', 1);
                                    $query->orWhere(function ($query) {
                                        $query->where('petitions.type', 2);
                                        $query->where('petitions.type_off', 2);
                                    });
                                })
                                ->get();

            if (count($petitions) > 0) {
                foreach ($petitions as $petition) {
                    $isCheckout = UserCheckout::leftJoin('users', 'users.user_code', '=', 'user_checkouts.user_code')
                                            ->where('users.id', $petition->user_id)
                                            ->whereDate('user_checkouts.date', $petition->start_date)
                                            ->first();

                    if (!$isCheckout) {
                        $log = TimesheetDetail::select(DB::raw('max(time) as check_out'), 'user_code', 'date')
                                            ->where('user_code', $petition->user_code)
                                            ->where('date', $petition->start_date)
                                            ->groupBy('user_code', 'date')
                                            ->first();

                        if ($log) {
                            //start transaction
                            DB::beginTransaction();
                            
                            $checkout = UserCheckout::create([
                                'user_code' => $petition->user_code,
                                'check_out' => $log->check_out,
                                'date' => $petition->start_date
                            ]);

                            DB::commit();

                            //log the record that has been saved successfully
                            $message = 'user_id: '.$petition->user_id.' has been inserted log checkout. Id checkout: ';
                            Log::info($message.$checkout->id);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::error($e);
        }
    }
}
