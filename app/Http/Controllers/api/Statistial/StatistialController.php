<?php
namespace App\Http\Controllers\api\Statistial;

use App\Http\Controllers\api\CommonController;
use App\Http\Requests\api\Statistial\StatistialTopRequest;
use App\Models\Holiday;
use App\Models\HolidayOffset;
use App\Models\TimesheetDetail;
use App\Models\Violation;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StatistialController
{
    public function statistialTop(StatistialTopRequest $request)
    {
        $requestDatas = $request->all();
        $data = CommonController::getTimesheetReport($requestDatas);

        if (!is_array($data) || !isset($data[0])) {
            return response()->json([]);
        }
        foreach ($data as $val) {

            if(!empty($requestDatas['department_id']) && $val['department_id'] != $requestDatas['department_id']) continue;

            # ngay lam viec chuan time range of employee
            $requestDataClone = $requestDatas;
            $timeUserStartWorking = $val['created_at'];
//            $timeUserStartWorking = $val['date_official'] ? $val['date_official'] : $val['created_at'];
            // $timeUserStartWorking =  $this->getTimeUserStartWorking($val['user_code']);

            # if end_date == current date => end_date -= 1
            if(Carbon::parse($requestDataClone['end_date'])->gte(Carbon::parse(Carbon::now()->format('Y-m-d')))){
                $requestDataClone['end_date'] = Carbon::now()->subDay()->format('Y-m-d');
            }

            $timeUserStartWorkingCompare = Carbon::parse($timeUserStartWorking);

            # if time user start working >= request start date => set start_date = time user working
            if($timeUserStartWorkingCompare->gte(Carbon::parse($requestDataClone['start_date']))){
                $requestDataClone['start_date'] = $timeUserStartWorking;
            }
            $workDayStandardOfUser = $this->getHolidayListForMonth($requestDataClone) ? $this->getHolidayListForMonth($requestDataClone)['expectWorkDays'] : 0;

            $goEarlyTotalSum = array_reduce($val['timesheets'], function ($carry, $timesheet) {
                if (isset($timesheet['go_early_total']) && $timesheet['go_early_total'] != "") {
                    $carry['go_early_total'] += $timesheet['go_early_total'];
                    $carry['total_effort_hour'] += $timesheet['go_early_total'];
                }
                if (isset($timesheet['leave_late_total']) && $timesheet['leave_late_total'] != "") {
                    $carry['leave_late_total'] += $timesheet['leave_late_total'];
                    $carry['total_effort_hour'] += $timesheet['leave_late_total'];
                }
                if (isset($timesheet['late_total']) && $timesheet['late_total'] != "" && $timesheet['late_total'] != 0 && (!isset($timesheet['petition_type']) || !in_array(1, $timesheet['petition_type']))) {
                    $carry['late_total'] ++;
                }
                if (isset($timesheet['workday']) && $timesheet['workday'] != "" && (!isset($timesheet['petition_type']) || !in_array(2, $timesheet['petition_type']))) {
                    $carry['workday'] += $timesheet['workday'];
                }
                if (isset($timesheet['non_office_time_goouts']) && $timesheet['non_office_time_goouts'] > 0) {
                    $carry['non_office_time_goouts'] += $timesheet['non_office_time_goouts'];
                }
                return $carry;
            }, [
                'go_early_total' => 0,
                'leave_late_total' => 0,
                'late_total' => 0,
                'workday' => 0,
                'dayoff_total' => 0,
                'total_effort_hour' => 0,
                'non_office_time_goouts' => 0,
            ]);

            # calculate workday origin
            $workdayOrigin = $this->calculateWorkDay($val);
            $dayoff = $workDayStandardOfUser - $workdayOrigin;

            # calculate dayoff
            $rateDayoff = $dayoff && $workDayStandardOfUser ? round(($dayoff / $workDayStandardOfUser) * 100,2) : 0;

            # user vào sau time range request
//            if($timeUserStartWorkingCompare->gt(Carbon::parse($requestDataClone['end_date']))){
//                $rateDayoff = 0;
//           }

            $dataItem[] = [
                'id' => $val['id'],
                'fullname' => $val['fullname'],
                'avatar' => $val['avatar'],
                'department_id'=>$val['department_id'],
                'date_official' => $val['date_official'],
                'go_early_total' => $goEarlyTotalSum['go_early_total']/60/60,
                'leave_late_total' => $goEarlyTotalSum['leave_late_total']/60/60,
                'late_total' => $goEarlyTotalSum['late_total'],
                'total_day_work' => $workdayOrigin,
                'rate_dayoff' => max($rateDayoff, 0),
                'rate_late' => ($goEarlyTotalSum['workday'] != 0) ? (($goEarlyTotalSum['late_total']) / $goEarlyTotalSum['workday']) * 100 : 0,
                'total_effort_hour' => ($goEarlyTotalSum['total_effort_hour'] - $goEarlyTotalSum['non_office_time_goouts'])/60/60,
            ];
        }

        # merge chart
        $allData['rate_dayoff'] = $this->formatDataTable($dataItem,'rate_dayoff', $requestDatas['sort_options']); // ti le nghi
        $allData['go_early_total'] = $this->formatDataChart($dataItem,'go_early_total',$requestDatas['sort_options']); // di som
        $allData['total_effort_hour'] = $this->formatDataChart($dataItem,'total_effort_hour',$requestDatas['sort_options']); // h no luc
        $allData['leave_late_total'] = $this->formatDataChart($dataItem,'leave_late_total',$requestDatas['sort_options']); // ve muon
        $allData['violations'] = $this->getworkBarChart($requestDatas, Violation::class, 'violations', 'COUNT(users.id)', 'time'); // total vi pham
        $allData['rate_late'] = $this->formatDataChart($dataItem,'rate_late',$requestDatas['sort_options']); // ti le di muon
        $allData['total_day_work'] = $this->formatDataTable($dataItem,'total_day_work',$requestDatas['sort_options']); // tong so ngay cong

        return $allData;
    }

    private function formatDataChart($dataItem, $sort, $sortOptionsRequest){
        // filter chart request
        $dataItem = $this->sortData($dataItem,$sort, $sortOptionsRequest);
        $limit = $sortOptionsRequest[$sort]['sort_quantity'];

        $dataItems = array_slice($dataItem, 0, $limit);
        $allData['user'] = [];
        $allData['dataChart'] = [];

        foreach ($dataItems as $key=>$val) {
            $allData['user'][$key]['fullname'] = $val['fullname'];
            $allData['user'][$key]['department_id'] = $val['department_id'];
            $allData['dataChart'][] = $val[$sort];
            $allData['late'][] = $val['late_total'];
        }
        return $allData;
    }

    private function formatDataTable($dataItem, $sort, $sortOptionsRequest){
        $limit = $sortOptionsRequest[$sort]['sort_quantity'];
        $dataItem = $this->sortData($dataItem,$sort, $sortOptionsRequest);
        $dataItems = array_slice($dataItem, 0, (int)$limit);

        $allData['user'] = [];
        $allData['dataChart'] = [];

        foreach ($dataItems as $key=>$val) {
            $allData['user'][$key]['fullname'] = $val['fullname'];
            $allData['user'][$key]['avatar'] = $val['avatar'];
            $allData['user'][$key]['date_official'] = $val['date_official'];
            if($sort == 'total_day_work'){
                $allData['user'][$key]['total_day_work'] = $val['total_day_work'];
            }else{
                $allData['user'][$key]['rate_dayoff'] = $val['rate_dayoff'];
            }
            $allData['user'][$key]['department_id'] = $val['department_id'];
            $allData['late'][] = $val['late_total'];
        }
        return $allData;
    }

    private function sortData($dataItem, $sort, $sortOptionsRequest)
    {
        $sortType = $sortOptionsRequest[$sort]['sort_type'];

        switch($sortType){
            case 'topFiveHighest': // high to low
                usort($dataItem, function ($a, $b) use ($sort) {
                    return $b[$sort] <=> $a[$sort];
                });
                break;
            default: // low to high
                usort($dataItem, function ($a, $b) use ($sort) {
                    return ($a[$sort] < $b[$sort]) ? -1 : 1;
                });
                break;
        }

        return $dataItem;
    }

    // calculate violations
    private function getworkBarChart($requestDatas, $modelClass, $tableName, $value, $date)
    {
        $model = new $modelClass;
        $orderBy = 'desc';
        $sortType = $requestDatas['sort_options']['violations']['sort_type'];
        $sortQty = $requestDatas['sort_options']['violations']['sort_quantity'];

        switch($sortType){
            case 'topFiveHighest': // high to low
                $orderBy = 'desc';
                break;
            default: //low to high
                $orderBy = 'asc';
                break;
        }

//        $departments = config('const.departments');
        // setup departments
//        $departments = array_map(function ($id, $name) {
//            return ['id' => $id, 'name' => $name];
//        }, array_keys($departments), $departments);
        // filter departments
//        $filteredDepartments = array_values(array_filter($departments, function ($department) {
//            $userDepartmentId = Auth()->user()->department_id;
//            return ($userDepartmentId == 12) ? ($department['id'] == 12) : ($department['id'] != 12);
//        }));

        $departmentId = $requestDatas['department_id'] ?? null;

        $data = $model->selectRaw(
                DB::raw(
                    'max(user_id) as max_user_id,
                    user_id,
                    users.fullname,
                    users.department_id,
                    coalesce(COUNT(violations.user_id), 0) AS count'
                )
            )
            ->leftJoin('users', $tableName.'.user_id', '=', 'users.id')
            ->whereBetween($date, [
                Carbon::parse($requestDatas['start_date'])->startOfMonth(),
                Carbon::parse($requestDatas['end_date'])->endOfMonth()
            ]);

            if($departmentId){
                $data = $data->where('users.department_id', $departmentId);
            }

            $data = $data->groupBy($tableName.'.user_id', 'users.fullname','users.department_id')
            ->orderBy('count', $orderBy)
            ->limit($sortQty)
            ->get();

        $dataTotal['dataChart'] = [];
        if(count($data) != 0){
            foreach ($data as $key=>$val) {
                $dataTotal['user'][$key]['fullname'] = $val->fullname;
                $dataTotal['user'][$key]['department_id'] = $val->department_id;
                $dataTotal['dataChart'][] = $val->count;
            }
        }
        return $dataTotal;
    }

    // top warrior nam
    public function statistialTopWarriorYear(Request $request){
        $requestDatas = $request->all();
        $resultArray = [];
        $resultArrayMonth = [1=>[],2=>[],3=>[],4=>[],5=>[],6=>[],7=>[],8=>[],9=>[],10=>[],11=>[],12=>[]];

        $data = CommonController::getTimesheetReport($requestDatas);
        $employees = $this->formatTimesheetToMonth($data);

        $holidayOffsets = HolidayOffset::select('id', 'offset_date', 'holiday_id', 'workday')
            ->whereBetween('offset_date', [$requestDatas['start_date'], $requestDatas['end_date']])->get();

        // Iterate through each employee and calculate the data
        foreach ($employees as $employee){
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

            if(!empty($requestDatas['department_id']) && $employee['department_id'] != $requestDatas['department_id']) continue;

            // Iterate through each row in the employee's timesheets
            foreach ($employee['timesheets'] as $month => $timesheet) {
                if(empty($timesheet[0])) continue;

                foreach ($timesheet as $itemTimesheet){
                    $date = array_keys($itemTimesheet)[0];
                    $item = array_shift($itemTimesheet);

                    // Check the condition and update sum and count petitions go late
                    $isPeGoLate = isset($item['is_petition_go_late']) && $item['is_petition_go_late'];
                    $isLate = isset($item['late_total']) && $item['late_total'] > 0;
                    $peLateCount += $isPeGoLate ? 1 : 0;
                    $lateCount += $isLate && !$isPeGoLate ? 1 : 0;
                    $lateSum += $isLate ? $item['late_total'] : 0;

                    // Check the condition and update sum and count petitions leave early
                    $isPeLeaveEarly = isset($item['is_petition_leave_early']) && $item['is_petition_leave_early'];
                    $isLeaveEarly = isset($item['early_total']) && $item['early_total'] > 0;
                    $peEarlyCount += $isPeLeaveEarly ? 1 : 0;
                    $earlyCount += $isLeaveEarly && !$isPeLeaveEarly ? 1 : 0;
                    $earlySum += $isLeaveEarly ? $item['early_total'] : 0;

                    // Check the condition and update sum and count employees personals goout with petitions and button in offices time
                    $gooutCount += $item['office_goouts'] ?? 0;
                    $gooutSum += $item['office_time_goouts'] ?? 0;
                    $nonOfficeGooutCount += $item['non_office_goouts'] ?? 0;
                    $nonOfficeGooutSum += $item['non_office_time_goouts'] ?? 0;

                    // Check if 'petition_type' exists and contains the value 2 (petitions leave)
                    $isExistPeType = isset($item['petition_type']) && in_array(2, $item['petition_type']);
                    $leaveCount = (isset($item['long_leave']) && (in_array($item['long_leave'], [1,2]))) ? 1 : 0.5;
                    // case 0.5 paid_leave and 0.5 un_paid_leave
                    if (isset($item['is_paid_leave']) && $isExistPeType && isset($item['long_leave']) && $item['long_leave'] == 1 && in_array($item['petition_type_off'], [1,2] ) ) {
                        $paidLeaveSum += 0.5;
                        $unPaidLeaveSum += 0.5;
                    } else if (isset($item['is_paid_leave']) && ($isExistPeType || $leaveCount === 1)) {

                        $paidLeaveSum += Carbon::parse((string)$date)->isSaturday() && isset($item['long_leave']) && (in_array($item['long_leave'], [2])) ? 0.5 : $leaveCount;
                        // $paidLeaveSum += $leaveCount;

                        if ($leaveCount === 1) {
                            $paidWorkday += Carbon::parse((string)$date)->isSaturday() && isset($item['long_leave']) && (in_array($item['long_leave'], [2])) ? 0.5 : $leaveCount;
                            // $paidLeaveSum += $leaveCount;
                        }
                    } else if (!isset($item['is_paid_leave']) && ($isExistPeType || $leaveCount === 1)) {
                        // Check if the holiday's id exists in $holidayOffsets
                        $matchingOffset = $holidayOffsets->firstWhere('offset_date', Carbon::parse((string)$date)->format('Y-m-d'));
                        if (!$matchingOffset && Carbon::parse((string)$date)->isSaturday() && $leaveCount === 1) {
                            $leaveCount = 0.5;
                        }
                        $unPaidLeaveSum += $leaveCount;
                    }

                    // Check if 'extra_workdays' exist and contain the key $date (employee request petition do extra workday)
                    $isExtraWorkday = isset($employee['extra_workdays']);
                    if ($isExtraWorkday && isset($employee['extra_workdays'][$date])) {
                        $extraWorkday += $item['workday'] ?? 0;
                    }

                    // Total time go early
                    $goEarlySum += $item['go_early_total'] ?? 0;
                    // Total time leave early
                    $leaveLateSum += $item['leave_late_total'] ?? 0;
                    // Total do extra warrior time
                    $extraWarriorTime += $item['extra_warrior_time'] ?? 0;

                    // Total paid workday
                    $paidWorkday += $item['workday'] ?? 0;
                }

                $resultArrayMonth[$month] = [
                    'go_early_sum' => $goEarlySum,
                    'late_sum' => $lateSum,
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
                    'origin_workday' => max(0, $paidWorkday - ($paidLeaveSum + $extraWorkday)),
                    'paid_workday' => $paidWorkday
                ];

                // reset
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
            }

            // Create a new array
            $resultArray[] = [
                'id' => $employee['id'],
                'fullname' => $employee['fullname'],
                'avatar' => $employee['avatar'],
                'date_official' => $employee['date_official'],
                'department_id' => $employee['department_id'],
                'timesheets'=>$resultArrayMonth
            ];
        }

        $workDay = $this->getHolidayListForRangeMonth($requestDatas);
        //get current workday from start of the month to current day
        $currentStartEnd = [
            'start_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d')
        ];
        $currentWorkDay = $this->getHolidayListForMonth($currentStartEnd);

        $data = [
            'current_work_day' => $currentWorkDay,
            'work_day' => $workDay,
            'users' => $resultArray,
        ];

        return response()->json($data);
    }

    private function formatTimesheetToMonth($data)
    {
        $timesheetFormat = [1=>[],2=>[],3=>[],4=>[],5=>[],6=>[],7=>[],8=>[],9=>[],10=>[],11=>[],12=>[]];
        foreach($data as $key=>$item){
            foreach($item['timesheets'] as $keyTimesheet=>$timesheet){
                $timeString = substr($keyTimesheet,0,4).'-'.substr($keyTimesheet,4,2).'-'.substr($keyTimesheet,6,2);
                $month = Carbon::createFromDate($timeString)->month;
                $timesheetFormat[$month][] = [$keyTimesheet=>$timesheet];
            }
            $data[$key]['timesheets'] = $timesheetFormat;
            $timesheetFormat = [1=>[],2=>[],3=>[],4=>[],5=>[],6=>[],7=>[],8=>[],9=>[],10=>[],11=>[],12=>[]];
        }
        return $data;
    }

    // top so cong lam
    private function calculateWorkDay($employee)
    {
        try {
            $extraWorkday = 0;
            $paidWorkday = 0;
            $paidLeaveSum = 0;

            // Iterate through each row in the employee's timesheets
            foreach ($employee['timesheets'] as $date => $timesheet) {
                // Check if 'petition_type' exists and contains the value 2 (petitions leave)
                $isExistPeType = isset($timesheet['petition_type']) && in_array(2, $timesheet['petition_type']);
                $leaveCount = (isset($timesheet['long_leave']) && (in_array($timesheet['long_leave'], [1,2]))) ? 1 : 0.5;
                // case 0.5 paid_leave and 0.5 un_paid_leave
                if (isset($timesheet['is_paid_leave']) && $isExistPeType && isset($timesheet['long_leave']) && $timesheet['long_leave'] == 1 && in_array($timesheet['petition_type_off'], [1,2] ) ) {
                    $paidLeaveSum += 0.5;
                } else if (isset($timesheet['is_paid_leave']) && ($isExistPeType || $leaveCount === 1)) {

                    $paidLeaveSum += Carbon::parse((string)$date)->isSaturday() && isset($timesheet['long_leave']) && (in_array($timesheet['long_leave'], [2])) ? 0.5 : $leaveCount;

                    if ($leaveCount === 1) {
                        $paidWorkday += Carbon::parse((string)$date)->isSaturday() && isset($timesheet['long_leave']) && (in_array($timesheet['long_leave'], [2])) ? 0.5 : $leaveCount;
                    }
                }

                // Check if 'extra_workdays' exist and contain the key $date (employee request petition do extra workday)
                $isExtraWorkday = isset($employee['extra_workdays']);
                if ($isExtraWorkday && isset($employee['extra_workdays'][$date])) {
                    $extraWorkday += $timesheet['workday'] ?? 0;
                }

                // Total paid workday
                $paidWorkday += $timesheet['workday'] ?? 0;
            }

            $workdayOrigin = max(0, $paidWorkday - ($paidLeaveSum + $extraWorkday)); // cong thuc te
            return $workdayOrigin;
        } catch (\Throwable $e) {
            Log::error($e);
            return 0;
        }
    }

    // function handle timesheet
    private function getHolidayListForRangeMonth($requestDatas)
    {
        $rqStartDate = Carbon::parse($requestDatas['start_date'])->format('Y-m-d');
        $rqEndDate = Carbon::parse($requestDatas['end_date'])->format('Y-m-d');

        $holidayOffsets = HolidayOffset::select('id', 'offset_date', 'holiday_id', 'workday')
            ->whereBetween('offset_date', [$requestDatas['start_date'], $requestDatas['end_date']])->get();

        // arr range time 12 month 12 item [['start_date'=>'','end_date'=>''],[...]]
        $startDate = Carbon::parse($rqStartDate)->format('Y-m-d');
        $endDate = Carbon::parse($rqEndDate)->format('Y-m-d');
        $period = CarbonPeriod::create($startDate, '1 month', $endDate);
        $monthRange = array();
        foreach ($period as $date) {
            $monthRange[] = (object)[
                "start"=> ($date->firstOfMonth()->gt($startDate)) ? $date->firstOfMonth()->toDateString(): $startDate,
                "end"=> ($date->lastOfMonth()->lt($endDate)) ? $date->lastOfMonth()->toDateString() : $endDate
            ];
        }

        $dataFinal = [];

        foreach($monthRange as $mon=>$range){
            $holidays = Holiday::query()
                ->whereDate(
                    'holidays.start_date',
                    '<=',
                    $range->end
                )
                ->whereDate(
                    'holidays.end_date',
                    '>=',
                    $range->start
                )->get();

            $expectWorkDays = $this->countWorkDay($holidayOffsets, $range->start, $range->end);
            $workDay = [
                'expectWorkDays' => $expectWorkDays,
                'workDayHoliday' => 0,
            ];

            foreach ($holidays as $holiday) {
                $startDate = Carbon::create($holiday->start_date)->format("Y-m-d");
                $endDate = Carbon::create($holiday->end_date)->format("Y-m-d");

                $workDay = $this->countWorkDayWithHolidays(
                    $startDate,
                    $endDate,
                    $range->start,
                    $range->end,
                    $workDay
                );

                // Check if the holiday's id exists in $holidayOffsets
                $matchingOffset = $holidayOffsets->firstWhere('holiday_id', $holiday->id);
                if ($matchingOffset) {
                    // If a matching offset is found, subtract its workday value
                    $workDay['workDayHoliday'] -= $matchingOffset->workday;
                }
            }
            $dataFinal[$mon+1] = $workDay;
        }


        return $dataFinal;
    }

    private function getHolidayListForMonth($requestDatas)
    {
        $holidayOffsets = HolidayOffset::select('id', 'offset_date', 'holiday_id', 'workday')
            ->whereBetween('offset_date', [$requestDatas['start_date'], $requestDatas['end_date']])->get();

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

    private function getTimeUserStartWorking($userCode)
    {
        try{
            $timesheetDetail = TimesheetDetail::where('user_code',$userCode)
                                ->first();
            return $timesheetDetail->date;
        }catch(\Throwable $th){
            logger($th->getMessage());
            return false;
        }
    }
}
