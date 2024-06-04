<?php

namespace App\Http\Controllers\api\Timesheet;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api\CommonController;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Timesheet;
use App\Models\Petition;
use App\Models\Holiday;
use App\Models\HolidayOffset;
use App\Models\User;
use App\Models\UserGoOut;
use App\Models\TimesheetDetail;
use App\Http\Requests\api\Timesheet\GetTimesheetListRequest;
use App\Http\Requests\api\Timesheet\GetReportRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\UserCheckout;

/**
 * Timesheet API
 *
 * @group Timesheet
 */
class TimesheetController extends Controller
{
    public function getTimesheetList(GetTimesheetListRequest $request)
    {
        try {
            $requestDatas = $request->all();

            $data = CommonController::getTimesheetReport($requestDatas);

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }
    public function updateCheckOut(Request $request)
    {
        try {
            $requestDatas = $request->all();
            if (Auth()->user()->id != 161 && Auth()->user()->id != 194) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => 'Bạn không có quyền cập nhật log check out'
                ], Response::HTTP_NOT_FOUND);
            }
            $log = TimesheetDetail::select(DB::raw('max(time) as check_out'), 'user_code', 'date')->where('date', $requestDatas['date']);

            if (isset($requestDatas['user_code']) && count($requestDatas['user_code']) > 0 && (!isset($requestDatas['all_log']) || $requestDatas['all_log'] == false)) {
                $log->whereIn('user_code', $requestDatas['user_code']);
            }
            $log = $log->groupBy('user_code', 'date')->get();

            if ($log && count($log) > 0) {
                DB::beginTransaction();
                foreach ($log as $key => $val) {
                    $logCheckOut = UserCheckout::select('id')
                        ->where('date', $requestDatas['date'])
                        ->where('user_code', $val->user_code)
                        ->first();
                    if ($logCheckOut) {
                        $checkout = UserCheckout::where('id', $logCheckOut->id)
                        ->where('final_checkout', false)
                        ->update([
                            'check_out' => $val->check_out,
                        ]);
                    } else {
                        $checkout = UserCheckout::create([
                            'user_code' => $val->user_code,
                            'check_out' => $val->check_out,
                            'date' => $requestDatas['date'],
                            'final_checkout' => false
                        ]);
                    }
                }
                DB::commit();
            }

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getReport(GetReportRequest $request)
    {
        try {
            $requestDatas = $request->all();
            // Initialize a new collection to store the results
            $resultArray = [];

            $requestDatas['timesheet_report'] = true;
            $employees = CommonController::getTimesheetReport($requestDatas);

            $holidayOffsets = HolidayOffset::select('id', 'offset_date', 'holiday_id', 'workday')
                ->whereBetween('offset_date', [$requestDatas['start_date'], $requestDatas['end_date']])->get();

            // Iterate through each employee and calculate the data
            collect($employees)->each(function ($employee) use (&$resultArray, $holidayOffsets,$requestDatas) {
                $goEarlySum = 0;
                $leaveLateSum = 0;

                $lateSum = 0;
                $lateCount = 0;
                $peLateCount = 0;

                $earlySum = 0;
                $earlyCount = 0;
                $peEarlyCount = 0;

                $extraWarriorTime = 0;

                $extraWorkday = 0;
                $paidWorkday = 0;

                $gooutSum = 0;
                $gooutCount = 0;
                $nonOfficeGooutCount = 0;
                $nonOfficeGooutSum = 0;

                $paidLeaveSum = 0;
                $unPaidLeaveSum = 0;

                $lateSumNonePetition = 0;
                $leaveHoliday = 0;

                $workdayHoliday = 0;
                
                // Iterate through each row in the employee's timesheets
                foreach ($employee['timesheets'] as $date => $timesheet) {
                    if (Carbon::parse((string)$date)->format('Y/m/d') >= $requestDatas['start_date'] && Carbon::parse((string)$date)->format('Y/m/d') <= $requestDatas['end_date']) {
                        // Check the condition and update sum and count petitions go late
                        $isPeGoLate = isset($timesheet['is_petition_go_late']) && $timesheet['is_petition_go_late'];
                        $isLate = isset($timesheet['late_total']) && $timesheet['late_total'] > 0;
                        $peLateCount += $isPeGoLate ? 1 : 0;
                        $lateCount += $isLate && !$isPeGoLate ? 1 : 0;
                        $lateSum += $isLate ? $timesheet['late_total'] : 0;
                        $lateSumNonePetition += $isLate && !$isPeGoLate ? $timesheet['late_total'] : 0;

                        // Check the condition and update sum and count petitions leave early
                        $isPeLeaveEarly = isset($timesheet['is_petition_leave_early']) && $timesheet['is_petition_leave_early'];
                        $isLeaveEarly = isset($timesheet['early_total']) && $timesheet['early_total'] > 0;
                        $peEarlyCount += $isPeLeaveEarly ? 1 : 0;
                        $earlyCount += $isLeaveEarly && !$isPeLeaveEarly ? 1 : 0;
                        $earlySum += $isLeaveEarly ? $timesheet['early_total'] : 0;

                        // Check the condition and update sum and count employees personals goout with petitions and button in offices time
                        $gooutCount += $timesheet['office_goouts'] ?? 0;
                        $gooutSum += $timesheet['office_time_goouts'] ?? 0;
                        $nonOfficeGooutCount += $timesheet['non_office_goouts'] ?? 0;
                        $nonOfficeGooutSum += $timesheet['non_office_time_goouts'] ?? 0;

                        // Check if 'petition_type' exists and contains the value 2 (petitions leave)
                        $isExistPeType = isset($timesheet['petition_type']) && in_array(2, $timesheet['petition_type']);
                        $leaveCount = (isset($timesheet['long_leave']) && (in_array($timesheet['long_leave'], [1,2]))) ? 1 : 0.5;
                        // Check if the holiday's id exists in $holidayOffsets
                        $matchingOffset = $holidayOffsets->firstWhere('offset_date', Carbon::parse((string)$date)->format('Y-m-d'));
                        // case 0.5 paid_leave and 0.5 un_paid_leave
                        if (isset($timesheet['is_paid_leave']) && $isExistPeType && isset($timesheet['long_leave']) && $timesheet['long_leave'] == 1 && in_array($timesheet['petition_type_off'], [1,2] ) ) {
                            $paidLeaveSum += 0.5;
                            $unPaidLeaveSum += 0.5;
                        } else if (isset($timesheet['is_paid_leave']) && ($isExistPeType || $leaveCount === 1)) {

                            $paidLeaveSum += Carbon::parse((string)$date)->isSaturday() && isset($timesheet['long_leave']) && (in_array($timesheet['long_leave'], [2])) ? 0.5 : $leaveCount;
                            // $paidLeaveSum += $leaveCount;

                            if ($leaveCount === 1) {
                                $paidWorkday += Carbon::parse((string)$date)->isSaturday() && isset($timesheet['long_leave']) && (in_array($timesheet['long_leave'], [2])) ? 0.5 : $leaveCount;
                                // $paidLeaveSum += $leaveCount;
                            }
                        } else if (!isset($timesheet['is_paid_leave']) && ($isExistPeType || $leaveCount === 1)) {   
                            if (!$matchingOffset && Carbon::parse((string)$date)->isSaturday() && $leaveCount === 1) {
                                $leaveCount = 0.5;
                            }
                            $unPaidLeaveSum += $leaveCount;
                            
                            if (isset($timesheet['is_holiday'])) {
                                $leaveHoliday += $leaveCount;
                            }
                        }

                        // Check if 'extra_workdays' exist and contain the key $date (employee request petition do extra workday)
                        $isExtraWorkday = isset($employee['extra_workdays']);
                        if ($isExtraWorkday && isset($employee['extra_workdays'][$date])) {
                            $extraWorkday += $timesheet['workday'] ?? 0;
                        }

                        // Total time go early
                        $goEarlySum += $timesheet['go_early_total'] ?? 0;
                        // Total time leave early
                        $leaveLateSum += $timesheet['leave_late_total'] ?? 0;
                        // Total do extra warrior time
                        $extraWarriorTime += $timesheet['extra_warrior_time'] ?? 0;

                        // Total paid workday
                        $paidWorkday += round($timesheet['workday'] ?? 0, 2);

                        $workdayHoliday += $matchingOffset ? $matchingOffset->workday : 0;

                    }
                    
                }

                // Create a new array
                $resultArray[] = [
                    'id' => $employee['id'],
                    'fullname' => $employee['fullname'],
                    'date_official' => $employee['date_official'],
                    'go_early_sum' => $goEarlySum,
                    'late_sum' => $lateSum,
                    'late_sum_none_petition' => $lateSumNonePetition,
                    'late_count' => $lateCount,
                    'pe_late_count' => $peLateCount,
                    'early_sum' => $earlySum,
                    'early_count' => $earlyCount,
                    'leave_late_sum' => $leaveLateSum,
                    'pe_early_count' => $peEarlyCount,
                    'total_late_nd_early' => $lateSum + $earlySum,
                    'office_goouts' => $gooutCount,
                    'office_time_goouts' => $gooutSum,
                    'non_office_goouts' => $nonOfficeGooutCount,
                    'non_office_time_goouts' => $nonOfficeGooutSum,
                    'paid_leave' => $paidLeaveSum,
                    'un_paid_leave' => $unPaidLeaveSum,
                    'extra_warrior_time' => $extraWarriorTime,
                    'extra_workday' => $extraWorkday,
                    'origin_workday' => max(0, $paidWorkday - ($paidLeaveSum + $extraWorkday + $workdayHoliday)),
                    'paid_workday' => $paidWorkday - $workdayHoliday,
                    'leave_holiday' => $leaveHoliday,
                ];
            });

            $workDay = $this->getHolidayListForReport($holidayOffsets, $requestDatas);
            //get current workday from start of the month to current day
            $currentStartEnd = [
                'start_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'end_date' => Carbon::now()->format('Y-m-d')
            ];
            $currentWorkDay = $this->getHolidayListForReport($holidayOffsets, $currentStartEnd);

            $data = [
                'users' => $resultArray,
                'work_day' => $workDay,
                'current_work_day' => $currentWorkDay
            ];

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    private function getHolidayListForReport($holidayOffsets, $requestDatas)
    {
        $holidays = Holiday::query()
        ->whereDate(
            'holidays.start_date',
            '<=',
            Carbon::create($requestDatas['end_date'])->format('Y-m-d')
        )
        ->whereDate(
            'holidays.end_date',
            '>=',
            Carbon::create($requestDatas['start_date'])->format('Y-m-d')
        )
        ->get();

        $rqStartDate = Carbon::parse($requestDatas['start_date'])->format('Y-m-d');
        $rqEndDate = Carbon::parse($requestDatas['end_date'])->format('Y-m-d');

        $expectWorkDays = $this->countWorkDay($holidayOffsets, $rqStartDate, $rqEndDate);
        $workDay = [
            'expectWorkDays' => $expectWorkDays,
            'workDayHoliday' => 0,
        ];
        $workDay['expectWorkDays'] -= count($holidayOffsets) > 0 ? $holidayOffsets->first()->workday : 0;
        $workDay['workDayHoliday'] += count($holidayOffsets) > 0  ? $holidayOffsets->first()->workday : 0;

        foreach ($holidays as $holiday) {
            $startDate = Carbon::create($holiday->start_date)->format("Y-m-d");
            $endDate = Carbon::create($holiday->end_date)->format("Y-m-d");

            $workDay = $this->countWorkDayWithHolidays(
                $startDate,
                $endDate,
                $rqStartDate,
                $rqEndDate,
                $workDay
            );

            // Check if the holiday's id exists in $holidayOffsets
            $matchingOffset = $holidayOffsets->firstWhere('holiday_id', $holiday->id);
            if ($matchingOffset) {
                // If a matching offset is found, subtract its workday value
                $workDay['workDayHoliday'] -= $matchingOffset->workday;
            }
        }

        return $workDay;
    }

    private function countWorkDay($holidayOffsets, $startDate, $endDate)
    {
        $count = 0;
        $holidayOffsetsArray = $holidayOffsets->pluck('offset_date')->toArray();
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period->toArray() as $item) {
            switch ($item->format('l')) {
                case 'Monday':
                case 'Tuesday':
                case 'Wednesday':
                case 'Thursday':
                case 'Friday':
                    $count += 1;
                    break;
                case 'Saturday':
                    $workday = 0.5;
                    //holiday offset
                    if (in_array($item->format('Y-m-d'), $holidayOffsetsArray)) {
                        $workday = 1;
                    }

                    $count += $workday;
                    break;
                default:
                    # code...
                    break;
            }
        }

        return $count;
    }

    private function countWorkDayWithHolidays(
        $startHolidayDate,
        $endHolidayDate,
        $requestStartDate,
        $requestEndDate,
        $workDay
    ) {
        $requestStartDateYMD = Carbon::createFromFormat('Y-m-d', $requestStartDate)->startOfDay();
        $requestEndDateYMD = Carbon::createFromFormat('Y-m-d', $requestEndDate)->endOfDay();

        $period = CarbonPeriod::create($startHolidayDate, $endHolidayDate);

        foreach ($period->toArray() as $item) {
            if ($item->greaterThanOrEqualTo($requestStartDateYMD) && $item->lessThanOrEqualTo($requestEndDateYMD)) {
                switch ($item->format('l')) {
                    case 'Monday':
                    case 'Tuesday':
                    case 'Wednesday':
                    case 'Thursday':
                    case 'Friday':
                        $workDay['expectWorkDays'] --;
                        $workDay['workDayHoliday'] ++;
                        break;
                    case 'Saturday':
                        $workDay['expectWorkDays'] -= 0.5;
                        $workDay['workDayHoliday'] += 0.5;
                        break;
                    default:
                        # code...
                        break;
                }
            }
        }

        return $workDay;
    }

    public function getSession()
    {
        try {
            $record = TimesheetDetail::join('users', 'users.user_code', '=', 'timesheet_details.user_code')
                ->leftJoin('user_checkouts', function ($join) {
                    $join->on('user_checkouts.user_code', '=', 'timesheet_details.user_code');
                    $join->on('user_checkouts.date', '=', 'timesheet_details.date');
                })
                ->select('timesheet_details.id')
                ->where('users.id', Auth()->user()->id)
                ->where(function ($query) {
                    $query->whereNull('user_checkouts.check_out')
                          ->orWhere('user_checkouts.final_checkout', false);
                })
                ->whereDate('timesheet_details.date', Carbon::create(Carbon::now())->format('Y-m-d'))
                ->first();

            $isOut = UserGoOut::leftJoin('users', 'users.user_code', '=', 'user_go_outs.user_code')
                ->select('user_go_outs.id')
                ->where('users.id', Auth()->user()->id)
                ->where('user_go_outs.status', 1) //employee is out of work
                ->whereDate('date', Carbon::create(Carbon::now())->format('Y-m-d'))
                ->first();

            //get status checkin for employee Huynh Kieu Xuan Truong
            $isButtonCheckin = false;
            $isShowCheckout = false;
            if (Auth()->user()->id == 69 || Auth()->user()->id == 161) {
                $isShowCheckout = true;
                $log = TimesheetDetail::where('user_code', Auth()->user()->user_code)
                            ->where('date', Carbon::now()->format('Y/m/d'))
                            ->first();
                if (!$log) {
                    $isButtonCheckin = true;
                }
            }

            $data = [
                'is_authority' => Auth()->user()->permission > 0 ? true : false,
                'is_show' => $record ? true : false,
                'is_out' => $isOut ? true : false,
                'is_button_check_in' => $isButtonCheckin,
                'is_show_check_out' => $isShowCheckout,
                'is_user_id' => Auth()->user()->id,
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
}
