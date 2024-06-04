<?php

namespace App\Http\Controllers\api\Export;

use App\Http\Controllers\api\CommonController;
use App\Http\Controllers\Controller;
use App\Http\Requests\api\Statistial\ExportStatistialRequest;
use App\Http\Requests\api\Statistial\StatistialTopRequest;
use App\Models\Holiday;
use App\Models\HolidayOffset;
use App\Models\TimesheetDetail;
use App\Models\Violation;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Storage;

/**
 * Statistial Top Export API
 *
 * @group StatistialTopExport Export
 */
class StatistialTopExportController extends Controller
{
    # base export statistial top data
    public function exportStatistialTop(ExportStatistialRequest $request)
    {
        try {
            $requestDatas = $request->all();

            $templatePath = resource_path('templates/statistial_top_template.xlsx');

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
            //set value
            $this->fillData($spreadsheet,$requestDatas);
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            $filename = 'statistial_top_'.$requestDatas['type'].time().'.xlsx';
            $filePath = Storage::path('excels/'.$filename);

            //Save file
            $writer->save($filePath);

            return response()->download($filePath);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    private function fillData($spreadsheet,$requestDatas)
    {
        $data = $requestDatas;
        $row = 2;
        $startDate = isset($requestDatas['start_date']) ? Carbon::parse($requestDatas['start_date'])->format('Y-m-d') : Carbon::now()->format('Y-m-d');
        $endDate = isset($requestDatas['start_date']) ? Carbon::parse($requestDatas['end_date'])->format('Y-m-d') : Carbon::now()->format('Y-m-d');

        if($requestDatas['type'] == 'warrior_year'){ // export data table warrior
            $worksheet = $spreadsheet->getSheetByName($requestDatas['type']);
            if(!empty($data['warrior'])){
                $year = !empty($requestDatas['year']) ? $requestDatas['year'] : Carbon::now()->year;
                $titleSheet = 'Danh sách top warrior năm '.$year;
                $this->setExcelData($worksheet, 'A', 1, $titleSheet,'s',11);
                foreach ($data['warrior'] as $key => $value) {
                    $this->setExcelData($worksheet, 'A', $key+$row + 1, $value['fullname']);
                    $this->setExcelData($worksheet, 'B', $key+$row + 1, $this->getDepartmentName($value['department_id']));
                    $this->setExcelData($worksheet, 'C', $key+$row + 1, $value['total_work_date']);
                    $this->setExcelData($worksheet, 'D', $key+$row + 1, $value['month_warrior1']);
                    $this->setExcelData($worksheet, 'E', $key+$row + 1, $value['month_warrior2']);
                    $this->setExcelData($worksheet, 'F', $key+$row + 1, $value['month_warrior3']);
                    $this->setExcelData($worksheet, 'G', $key+$row + 1, $value['total_warrior']);
                }
            }
        }else if($requestDatas['type'] == 'all'){ // export data all chart statis
            $statistialData = $this->getStatistialData($requestDatas);

            foreach($statistialData as $chart=>$data){
                $worksheet = $spreadsheet->getSheetByName($chart);

                $titleSheet = 'Danh sách thống kê '.$this->getColumnTitle($chart). ' từ '. $startDate . ' đến ' .$endDate;
                $this->setExcelData($worksheet, 'A', 1, $titleSheet,'s',11);
                $this->setExcelData($worksheet, 'D', 2, ucfirst($this->getColumnTitle($chart)),'s',11);

                // fetch data
                try{
                    foreach ($data as $key => $value) {
                        $this->setExcelData($worksheet, 'A', $key+$row + 1, $value['fullname']);
                        $this->setExcelData($worksheet, 'B', $key+$row + 1, $value['department']);
                        $this->setExcelData($worksheet, 'C', $key+$row + 1, $value['date_official'] != '' ? $value['date_official'] : 'Thử việc');
                        $this->setExcelData($worksheet, 'D', $key+$row + 1, $value['value']);
                    }
                }catch(\Throwable $th){
                    continue;
                }
            }

            // data warrior from client
            $warriorData = $requestDatas['warrior_data'] ?? [];
            $worksheet = $spreadsheet->getSheetByName('warrior_year');
            if(!empty($warriorData['warrior'])){
                $year = !empty($warriorData['year']) ? $warriorData['year'] : Carbon::now()->year;
                $titleSheet = 'Danh sách top warrior năm '.$year;
                $this->setExcelData($worksheet, 'A', 1, $titleSheet,'s',11);
                foreach ($warriorData['warrior'] as $key => $value) {
                    $this->setExcelData($worksheet, 'A', $key+$row + 1, $value['fullname']);
                    $this->setExcelData($worksheet, 'B', $key+$row + 1, $this->getDepartmentName($value['department_id']));
                    $this->setExcelData($worksheet, 'C', $key+$row + 1, $value['total_work_date']);
                    $this->setExcelData($worksheet, 'D', $key+$row + 1, $value['month_warrior1']);
                    $this->setExcelData($worksheet, 'E', $key+$row + 1, $value['month_warrior2']);
                    $this->setExcelData($worksheet, 'F', $key+$row + 1, $value['month_warrior3']);
                    $this->setExcelData($worksheet, 'G', $key+$row + 1, $value['total_warrior']);
                }
            }

        }else{ // export data specific chart
            $worksheet = $spreadsheet->getSheetByName($requestDatas['type']);
            $titleSheet = 'Danh sách thống kê '.$this->getColumnTitle($requestDatas['type']). ' từ '. $startDate . ' đến ' .$endDate;
            $this->setExcelData($worksheet, 'A', 1, $titleSheet,'s',11);
            $this->setExcelData($worksheet, 'D', 2, ucfirst($this->getColumnTitle($requestDatas['type'])),'s',11);

            // fetch data
            $statistialData = $this->getStatistialData($requestDatas);

            foreach ($statistialData as $key => $value) {
                $this->setExcelData($worksheet, 'A', $key+$row + 1, $value['fullname']);
                $this->setExcelData($worksheet, 'B', $key+$row + 1, $value['department']);
                $this->setExcelData($worksheet, 'C', $key+$row + 1, $value['date_official'] != '' ? $value['date_official'] : 'Thử việc');
                $this->setExcelData($worksheet, 'D', $key+$row + 1, $value['value']);
            }
        }
    }

    private function setExcelData($worksheet, $col, $row, $data, $type_string = "s",$size=10,$bold=false)
    {
        $worksheet->getCell($col . $row)->setValueExplicit($data, $type_string);
        $style = $worksheet->getStyle($col . $row);
        $style->getFont()->setName('BIZ UD????')->getColor()->setRGB('000000');
        $style->getFont()->setSize($size)->setBold($bold);
    }

    private function getDepartmentName($id){
        $configDepartments = config('const.departments');
        return $configDepartments[$id];
    }

    private function getColumnTitle($key)
    {
        switch ($key){
            case 'go_early_total':
                $name =  'thời gian đi sớm';
                break;
            case 'total_effort_hour':
                $name = 'giờ nỗ lực';
                break;
            case 'leave_late_total':
                $name = 'thời gian về muộn';
                break;
            case 'violations':
                $name = 'số lần vi phạm kỷ luật';
                break;
            case 'rate_late':
                $name = 'tỉ lệ đi muộn';
                break;
            case 'total_day_work':
                $name = 'tổng số công đi làm';
                break;
            case 'warrior_year':
                $name = 'warrior năm';
             break;
            default: // 'rate_dayoff'
                $name = 'tỉ lệ nghỉ';
                break;
        }
        return $name;
    }

    /**
     * fetch data export
     */
    public function getStatistialData($requestDatas)
    {
        try{
            $data = CommonController::getTimesheetReport($requestDatas);

            if (!is_array($data) || !isset($data[0])) {
                return [];
            }
            foreach ($data as $val) {

                if(!empty($requestDatas['department_id']) && $val['department_id'] != $requestDatas['department_id']) continue;

                # ngay lam viec chuan time range of employee
                $requestDataClone = $requestDatas;
                $timeUserStartWorking = $val['created_at'];

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

            // export specific chart
            $dataRes = array();
            if($requestDatas['type'] == 'violations'){
                $dataRes = $this->getworkBarChart($requestDatas, Violation::class, 'violations', 'COUNT(users.id)', 'time');
            }else if($requestDatas['type'] == 'all'){
                $dataRes['rate_dayoff'] = $this->formatDataChart($dataItem,'rate_dayoff'); // ti le nghi
                $dataRes['total_effort_hour'] = $this->formatDataChart($dataItem,'total_effort_hour'); // h no luc
                $dataRes['leave_late_total'] = $this->formatDataChart($dataItem,'leave_late_total'); // ve muon
                $dataRes['violations'] = $this->getworkBarChart($requestDatas, Violation::class, 'violations', 'COUNT(users.id)', 'time'); // total vi pham
                $dataRes['rate_late'] = $this->formatDataChart($dataItem,'rate_late'); // ti le di muon
                $dataRes['total_day_work'] = $this->formatDataChart($dataItem,'total_day_work'); // tong so ngay cong
                $dataRes['go_early_total'] = $this->formatDataChart($dataItem,'go_early_total'); // di som
            }else{
                $dataRes = $this->formatDataChart($dataItem,$requestDatas['type']);
            }

            return $dataRes;
        }catch(\Throwable $th){
            Log::error($th->getMessage());
            return [];
        }
    }

    private function formatDataChart($dataItem, $sort){
        // filter chart request
        $dataItems = $this->sortData($dataItem,$sort);

        foreach ($dataItems as $key=>$val) {
            $allData[$key]['fullname'] = $val['fullname'];
            $allData[$key]['department'] = $this->getDepartmentName($val['department_id']);
            $allData[$key]['date_official'] = $val['date_official'];
            $allData[$key]['value'] = $val[$sort];
        }
        return $allData;
    }

    private function sortData($dataItem, $sort)
    {
        usort($dataItem, function ($a, $b) use ($sort) {
            return $b[$sort] <=> $a[$sort];
        });

        return $dataItem;
    }

    // calculate violations
    private function getworkBarChart($requestDatas, $modelClass, $tableName, $value, $date)
    {
        $model = new $modelClass;
        $orderBy = 'desc'; //topFiveHighest

//        $departments = config('const.departments');
//        // setup departments
//        $departments = array_map(function ($id, $name) {
//            return ['id' => $id, 'name' => $name];
//        }, array_keys($departments), $departments);
//        // filter departments
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
                    users.date_official,
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

            $data = $data->groupBy($tableName.'.user_id', 'users.fullname','users.department_id','users.date_official')
            ->orderBy('count', $orderBy)
            ->get();
        $dataTotal = [];
        if(count($data) != 0){
            foreach ($data as $key=>$val) {
                $dataTotal[$key]['fullname'] = $val->fullname;
                $dataTotal[$key]['department'] = $this->getDepartmentName($val->department_id);
                $dataTotal[$key]['date_official'] = Carbon::parse($val->date_official)->format('Y/m/d');
                $dataTotal[$key]['value'] = $val->count;
            }
        }
        return $dataTotal;
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
}
