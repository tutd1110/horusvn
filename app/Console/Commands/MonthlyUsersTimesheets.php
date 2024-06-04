<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\api\CommonController;
use App\Models\UserWork;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MonthlyUsersTimesheets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monthly:users-timesheets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly timesheets for users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $requestDatas['start_date'] = '2023/07/01';
            $requestDatas['end_date'] = '2023/07/31';
            $requestDatas['type'] = 'cronjob';

            $data = CommonController::getTimesheetReport($requestDatas);
            $mapData = collect($data)->map(function ($item) use ($requestDatas) {
                $timesheets = $item['timesheets'] ?? [];
                
                $workdaySum = collect($timesheets)
                    ->filter(function ($timesheet) {
                        return !(
                            (isset($timesheet['long_leave']) && in_array($timesheet['long_leave'], [1, 2])) ||
                            isset($timesheet['is_out_a_day']) || isset($timesheet['is_holiday'])
                        );
                    })
                    ->map(function ($timesheet) {
                        return isset($timesheet['petition_type_off']) &&
                            in_array($timesheet['petition_type_off'], [1, 2]) &&
                            isset($timesheet['workday']) && $timesheet['workday'] > 0.5
                            ? $timesheet['workday'] - 0.5
                            : ($timesheet['workday'] ?? 0);
                    })
                    ->sum();

                $effortTimeSum = collect($timesheets)
                    ->map(function ($timesheet) {
                        $goEarlyLeaveLateSum = ($timesheet['go_early_total'] ?? 0) + ($timesheet['leave_late_total'] ?? 0);
                        $adjustedSum = isset($timesheet['click_time_goouts'])
                            ? $goEarlyLeaveLateSum - $timesheet['click_time_goouts']
                            : $goEarlyLeaveLateSum;
                
                        return $adjustedSum/3600;
                    })
                    ->sum();

                $modifiedRequestDatas = array_merge($requestDatas, [
                    'user_id' => $item['id']
                ]);
                $effortTimeTitle = CommonController::getWarriorTitle($modifiedRequestDatas, $effortTimeSum);
                $warrior1Value = $effortTimeTitle === 'Warrior 1' ? 1 : 0;
                $warrior2Value = $effortTimeTitle === 'Warrior 2' ? 1 : 0;
                $warrior3Value = $effortTimeTitle === 'Warrior 3' ? 1 : 0;
                
                return [
                    'user_id' => $item['id'],
                    'month' => $requestDatas['start_date'],
                    'total_day' => $workdaySum,
                    'total_hour' => $workdaySum*8 + $effortTimeSum,
                    'total_effort_hour' => $effortTimeSum,
                    'warrior_1' => $warrior1Value,
                    'warrior_2' => $warrior2Value,
                    'warrior_3' => $warrior3Value,
                    'created_at' => Carbon::parse($requestDatas['start_date'])->startOfDay(),
                    'updated_at' => Carbon::parse($requestDatas['start_date'])->startOfDay(),
                ];
            });

            DB::beginTransaction();

            // Insert the data into the user_works table
            UserWork::insert($mapData->toArray());

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
        }
    }
}
