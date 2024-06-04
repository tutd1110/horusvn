<?php

namespace App\Http\Controllers\api\Export;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Task;
use App\Models\User;
use App\Models\Sticker;
use App\Models\Project;
use App\Models\Priority;
use Carbon\Carbon;
use Storage;
use File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Timesheet Report Export API
 *
 * @group Timesheet Report Export
 */
class TimesheetReportExportController extends Controller
{
    public function export(Request $request)
    {
        try {
            set_time_limit(300);

            if (empty($request->employees)) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $employees = $request->employees;
            $workDay = $request->work_day;
            $currentWorkDay = $request->current_work_day;
            $expectPeriodWorkDay = $request->expect_period_workday;
            $currentPeriodWorkDay = $request->current_period_workday;

            //Load template excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(
                resource_path('templates/timesheet_report_template.xlsx')
            );
            //set value
            $worksheet = $spreadsheet->getActiveSheet();
            $worksheet->setTitle('Timesheet Report');

            //set auto size column
            foreach ($worksheet->getColumnIterator() as $column) {
                $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }

            //header
            $this->setExcelData($worksheet, "A", 1, 'Thời gian thống kê');
            $this->setExcelData($worksheet, "J", 1, 'Công chuẩn');
            $this->setExcelData($worksheet, "N", 1, 'Thời gian làm việc chính thức dưới 3 năm');
            $this->setExcelData($worksheet, "N", 1, 'Thời gian làm việc chính thức trên 3 năm');

            $this->setExcelData($worksheet, "A", 2, 'Công nghỉ tiêu chuẩn theo công ty: '.$workDay['workDayHoliday']);
            $this->setExcelData($worksheet, "J", 2, 'Chuẩn tháng');
            $this->setExcelData($worksheet, "L", 2, 'Thực tế');
            $this->setExcelData($worksheet, "N", 2, 'Warrior 1');
            $this->setExcelData($worksheet, "Q", 2, 'Warrior 2');
            $this->setExcelData($worksheet, "T", 2, 'Warrior 3');
            $this->setExcelData($worksheet, "W", 2, 'Warrior 1');
            $this->setExcelData($worksheet, "Z", 2, 'Warrior 2');
            $this->setExcelData($worksheet, "AC", 2, 'Warrior 3');

            $this->setExcelData($worksheet, "A", 3, 'Dự kiến: '.$expectPeriodWorkDay);
            $this->setExcelData($worksheet, "J", 3, $workDay['expectWorkDays'] + $workDay['workDayHoliday']);
            $this->setExcelData($worksheet, "L", 3, $workDay['expectWorkDays']);
            $this->setExcelData($worksheet, "N", 3, $workDay['expectWorkDays']*2);
            $this->setExcelData($worksheet, "Q", 3, $workDay['expectWorkDays']*3);
            $this->setExcelData($worksheet, "T", 3, $workDay['expectWorkDays']*4);
            $this->setExcelData($worksheet, "W", 3, $workDay['expectWorkDays']);
            $this->setExcelData($worksheet, "Z", 3, $workDay['expectWorkDays']*2);
            $this->setExcelData($worksheet, "AC", 3, $workDay['expectWorkDays']*3);

            $this->setExcelData($worksheet, "A", 4, 'Hiện tại: '.$currentPeriodWorkDay);
            $this->setExcelData(
                $worksheet,
                "J",
                4,
                $currentWorkDay['expectWorkDays']+$currentWorkDay['workDayHoliday']
            );
            $this->setExcelData($worksheet, "L", 4, $currentWorkDay['expectWorkDays']);
            $this->setExcelData($worksheet, "N", 4, $currentWorkDay['expectWorkDays']*2);
            $this->setExcelData($worksheet, "Q", 4, $currentWorkDay['expectWorkDays']*3);
            $this->setExcelData($worksheet, "T", 4, $currentWorkDay['expectWorkDays']*4);
            $this->setExcelData($worksheet, "W", 4, $currentWorkDay['expectWorkDays']);
            $this->setExcelData($worksheet, "Z", 4, $currentWorkDay['expectWorkDays']*2);
            $this->setExcelData($worksheet, "AC", 4, $currentWorkDay['expectWorkDays']*3);
            //fill employees
            foreach ($employees as $key => $employee) {
                $this->setExcelData($worksheet, "A", $key+7, $employee['fullname']);
                $this->setExcelData(
                    $worksheet,
                    "B",
                    $key+7,
                    $employee['date_official'] != "Thử việc" ?
                        Carbon::create($employee['date_official'])->format('d/m/Y') :
                        $employee['date_official']
                );
                $this->setExcelData($worksheet, "C", $key+7, $employee['total_work_date']);
                $this->setExcelData($worksheet, "D", $key+7, $employee['late_count']);
                $this->setExcelData($worksheet, "E", $key+7, $employee['pe_late_count']);
                $this->setExcelData($worksheet, "F", $key+7, $employee['late_sum']);
                $this->setExcelData($worksheet, "G", $key+7, $employee['early_count']);
                $this->setExcelData($worksheet, "H", $key+7, $employee['pe_early_count']);
                $this->setExcelData($worksheet, "I", $key+7, $employee['early_sum']);
                $this->setExcelData($worksheet, "J", $key+7, $employee['office_goouts']);
                $this->setExcelData($worksheet, "K", $key+7, $employee['office_time_goouts']);
                $this->setExcelData($worksheet, "L", $key+7, $employee['non_office_goouts']);
                $this->setExcelData($worksheet, "M", $key+7, $employee['non_office_time_goouts']);
                $this->setExcelData($worksheet, "N", $key+7, $employee['total_late_nd_early']);
                $this->setExcelData($worksheet, "O", $key+7, $employee['workday_late_nd_early']);
                $this->setExcelData($worksheet, "P", $key+7, $employee['go_early_sum']);
                $this->setExcelData($worksheet, "Q", $key+7, $employee['leave_late_sum']);
                $this->setExcelData($worksheet, "R", $key+7, $employee['workday_extra_warrior_time']);
                $this->setExcelData($worksheet, "S", $key+7, $employee['extra_warrior_time']);
                $this->setExcelData($worksheet, "T", $key+7, $employee['total_time_ot_war']);
                $this->setExcelData($worksheet, "U", $key+7, $employee['current_title']);
                $this->setExcelData($worksheet, "V", $key+7, $employee['time_keep_title']);
                $this->setExcelData($worksheet, "W", $key+7, $employee['avg_hold_title']);
                $this->setExcelData($worksheet, "X", $key+7, $employee['next_title']);
                $this->setExcelData($worksheet, "Y", $key+7, $employee['time_next_title']);
                $this->setExcelData($worksheet, "Z", $key+7, $employee['avg_next_title']);
                $this->setExcelData($worksheet, "AA", $key+7, $employee['origin_workday']);
                $this->setExcelData($worksheet, "AB", $key+7, $employee['extra_workday']);
                $this->setExcelData($worksheet, "AC", $key+7, $employee['paid_leave']);
                $this->setExcelData($worksheet, "AD", $key+7, $employee['un_paid_leave']);
                $this->setExcelData($worksheet, "AE", $key+7, $employee['paid_workday']);
                $this->setExcelData(
                    $worksheet,
                    "AF",
                    $key+7,
                    0
                );
                $this->setExcelData($worksheet, "AG", $key+7, $employee['rate_late']);
            }



            //End
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            $filename = 'timesheet_report_'.time().'.xlsx';
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

    private function setExcelData($worksheet, $col, $row, $data, $type_string = "s")
    {
        $cellCoordinate = $col . $row;
        $cell = $worksheet->getCell($cellCoordinate);
        $cell->setValueExplicit($data, $type_string);

        if (is_numeric($data)) {
            // Format the numeric value with a comma as the decimal separator and a dot as the thousands separator
            $formattedValue = number_format($data, 2, ',', '.');

            // Set the formatted value in the cell
            $cell->setValueExplicit($formattedValue, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }

        $style = $worksheet->getStyle($cellCoordinate);
        $style->getFont()->setName('BIZ UD????')->getColor()->setRGB('000000');
        $style->getFont()->setSize(10)->setBold(false);
    }
}
