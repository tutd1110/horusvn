<?php

namespace App\Http\Controllers\api\Export;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use Illuminate\Support\Facades\Log;
use Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request as FacasRequest;


/**
 * Employee Export API
 *
 * @group EmployeeListExport
 */
class EmployeeListExportController extends Controller
{
    # base export statistial top data
    public function export(Request $request)
    {
        try {
            $requestDatas = $request->all();
            $templatePath = resource_path('templates/employee_list_template.xlsx');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
            //set value
            $this->fillData($spreadsheet, $requestDatas);
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            $filename = 'employee_list_'.time().'.xlsx';
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

    private function fillData($spreadsheet, $requestDatas)
    {
        $data = $this->getEmployeeList($requestDatas);
        $row = 2;

        $worksheet = $spreadsheet->getActiveSheet('Employees');

        // fetch data
        foreach ($data['employee'] as $key => $value) {
            $this->setExcelData($worksheet, 'A', $key+$row, $value['fullname']);
            $this->setExcelData($worksheet, 'B', $key+$row, $value['gender']);
            $this->setExcelData($worksheet, 'C', $key+$row, $value['phone']);
            $this->setExcelData($worksheet, 'D', $key+$row, $value['email']);
            $this->setExcelData($worksheet, 'E', $key+$row, $value['birthday']);
            $this->setExcelData($worksheet, 'F', $key+$row, $value['department_id']);
            $this->setExcelData($worksheet, 'G', $key+$row, $value['position']);
            $this->setExcelData($worksheet, 'H', $key+$row, $value['type']);
            $this->setExcelData($worksheet, 'I', $key+$row, $value['created_at']);
            $this->setExcelData($worksheet, 'J', $key+$row, $value['date_official']);
        }
    }

    private function setExcelData($worksheet, $col, $row, $data, $type_string = "s",$size=10,$bold=false)
    {
        $worksheet->getCell($col . $row)->setValueExplicit($data, $type_string);
        $style = $worksheet->getStyle($col . $row);
        $style->getFont()->setName('BIZ UD????')->getColor()->setRGB('000000');
        $style->getFont()->setSize($size)->setBold($bold);
    }

    private function getEmployeeList($requestDatas){
        try {
            $protocol = FacasRequest::getScheme();
            $rootDomainWithProtocol = $protocol . '://' . request()->server->get('SERVER_NAME');

            $urlApi = $rootDomainWithProtocol.'/api/employee/get_employee_list';
            $token = auth()->user()->createToken('auth-token')->plainTextToken;
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])->post($urlApi, $requestDatas);

            // Process $data as needed
            $data = $response->json();
            return $data;
        } catch (\Throwable $th) {
            dd($th->getMessage());
            Log::error($th->getMessage());
            return [];
        }
    }
}
