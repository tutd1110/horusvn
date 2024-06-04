<?php

namespace App\Http\Controllers\api\Export;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Storage;

/**
 * Report Export API
 *
 * @group Report Export
 */
class ReportExportController extends Controller
{
    public function export(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            //Load template excel
            $templatePath = resource_path('templates/task_report_template.xlsx');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);

            //set value
            $this->fillDataDepartmentsExcel($spreadsheet, $requestDatas['departments']);
            $this->fillDataEmployeesExcel($spreadsheet, $requestDatas['employees']);

            //End
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            $filename = 'task_report_'.time().'.xlsx';
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

    private function fillDataDepartmentsExcel($spreadsheet, $departments)
    {
        $row = 2;
        $worksheet = $spreadsheet->getSheetByName('Departments');
        foreach ($departments as $key => $value) {
            //Department name
            $this->setExcelData($worksheet, 'A', $key+$row, $value['department']);
            //Total
            $this->setExcelData($worksheet, 'B', $key+$row, $value['total']);
            //Processing
            $this->setExcelData($worksheet, 'C', $key+$row, $value['total_processing']);
            //Slow
            $this->setExcelData($worksheet, 'D', $key+$row, $value['total_slow']);
            //Waiting
            $this->setExcelData($worksheet, 'E', $key+$row, $value['total_wait']);
            //Pending
            $this->setExcelData($worksheet, 'F', $key+$row, $value['total_pause']);
            //Wait feedback
            $this->setExcelData($worksheet, 'G', $key+$row, $value['total_wait_fb']);
            //Again
            $this->setExcelData($worksheet, 'H', $key+$row, $value['total_again']);
            //Completed
            $this->setExcelData($worksheet, 'I', $key+$row, $value['total_completed']);
            //Weight
            $this->setExcelData($worksheet, 'J', $key+$row, $value['total_weight']);
            //Weight completed
            $this->setExcelData($worksheet, 'K', $key+$row, $value['total_weight_completed']);
            //Percent task completed
            $this->setExcelData($worksheet, 'L', $key+$row, $value['rate_task_completed']);
            //Percent weight completed
            $this->setExcelData($worksheet, 'M', $key+$row, $value['rate_weight_completed']);
        }
    }

    private function fillDataEmployeesExcel($spreadsheet, $employees)
    {
        $row = 2;
        $worksheet = $spreadsheet->getSheetByName('Employees');
        foreach ($employees as $key => $value) {
            //fullname
            $this->setExcelData($worksheet, 'A', $key+$row, $value['fullname']);

            $this->setExcelData($worksheet, 'B', $key+$row, $value['department_name']);
            //total
            $this->setExcelData($worksheet, 'C', $key+$row, $value['total']);
            //total_completed
            $this->setExcelData($worksheet, 'D', $key+$row, $value['total_completed']);
            //total_slow
            $this->setExcelData($worksheet, 'E', $key+$row, $value['total_slow']);
            //total_processing
            $this->setExcelData($worksheet, 'F', $key+$row, $value['total_processing']);
            //total_pause
            $this->setExcelData($worksheet, 'G', $key+$row, $value['total_pause']);
            //total_wait
            $this->setExcelData($worksheet, 'H', $key+$row, $value['total_wait']);
            //total_wait_fb
            $this->setExcelData($worksheet, 'I', $key+$row, $value['total_wait_fb']);
            //total_again
            $this->setExcelData($worksheet, 'J', $key+$row, $value['total_again']);
            //total_slow
            $this->setExcelData($worksheet, 'K', $key+$row, $value['total_slow']);
            //rate_task_completed
            $this->setExcelData($worksheet, 'L', $key+$row, $value['rate_task_completed']);
            //total_weight_employee
            $this->setExcelData($worksheet, 'M', $key+$row, $value['total_weight_employee']);
            //total_weight_employee_completed
            $this->setExcelData($worksheet, 'N', $key+$row, $value['total_weight_employee_completed']);
            //rate_weight_completed
            $this->setExcelData($worksheet, 'O', $key+$row, $value['rate_weight_completed']);
            //rate_weight_project
            $this->setExcelData($worksheet, 'P', $key+$row, $value['rate_weight_project']);
            //rate_weight_department
            $this->setExcelData($worksheet, 'Q', $key+$row, $value['rate_weight_department']);
        }
    }

    private function setExcelData($worksheet, $col, $row, $data, $type_string = "s")
    {
        $worksheet->getCell($col . $row)->setValueExplicit($data, $type_string);
        $style = $worksheet->getStyle($col . $row);
        $style->getFont()->setName('BIZ UD????')->getColor()->setRGB('000000');
        $style->getFont()->setSize(10)->setBold(false);
    }
}
