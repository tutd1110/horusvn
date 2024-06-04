<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Petition;
use App\Models\User;
use App\Models\TimesheetDetail;
use Exception;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InsertLogCheckIn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert_log_check_in:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert log check in when employee could not checkin with cameras error...';

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
                                    'petitions.start_date as start_date',
                                    'petitions.status as status',
                                    'petitions.start_time_change as start_time_change'
                                )
                                ->whereDate('petitions.start_date', '2023-02-25')
                                ->where(function ($query) {
                                    $query->where('petitions.type', 4);
                                })
                                ->where('petitions.status', 1)
                                ->get();

            if (count($petitions) > 0) {
                foreach ($petitions as $petition) {
                    $log = TimesheetDetail::select('user_code', 'date')
                                        ->where('user_code', $petition->user_code)
                                        ->where('date', $petition->start_date)
                                        ->first();

                    if (!$log) {
                        //start transaction
                        DB::beginTransaction();
                        
                        //insert timesheet details
                        $checkin = TimesheetDetail::create([
                            'user_code' => $petition->user_code,
                            'detected_image_url' => null,
                            'device_id' => null,
                            'time_int' => strtotime($petition->start_date.' '.$petition->start_time_change),
                            'time' => $petition->start_time_change,
                            'date' => $petition->start_date,
                            'person_title' => null,
                            'json_data' => json_encode(['reason' => 'checkin_by_command']),
                        ]);

                        DB::commit();

                        //log the record that has been saved successfully
                        $message = 'user_id: '.$petition->user_id.' has been inserted log to timesheet_details table.';
                        Log::info($message.' Id: '.$checkin->id);
                    }
                }
            }
        } catch (Exception $e) {
            Log::error($e);
        }
    }
}
