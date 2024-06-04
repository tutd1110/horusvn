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
 * WZAndroid0022 Export API
 *
 * @group WZAndroid0022 Export
 */
class WZAndroid0022ExportController extends Controller
{
    public function export(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            if (!isset($requestDatas['report'])) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-I-008')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            //Load template excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(resource_path('templates/WZAndroid0022.xlsx'));
            // Create a new worksheet called "Data"
            $worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Data');
            $spreadsheet->addSheet($worksheet, 0);

            $worksheet = $spreadsheet->getSheetByName('Data');

            //action
            if (empty($requestDatas['report'])) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-I-008')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $report = collect($requestDatas['report']);

            $headers = array_keys($report->first());

            $keys = [
                "Method",
                "Name",
                "Group",
                "GroupDetail1",
                "GroupDetail2",
                "ItemId",
                "User",
                "PercentUser",
                "Total",
                "PercentTotal",
                "AVG"
            ];
            $sortedHeaders = array_intersect($keys, $headers);
            $sortedHeaders = array_merge($sortedHeaders, array_diff($headers, $sortedHeaders));

            // loop through the keys and set the column headers in the first row
            foreach ($sortedHeaders as $index => $key) {
                $column = chr(65 + $index);

                switch ($key) {
                    case 'GroupDetail1':
                        $header = 'Group Detail 1';
                        break;
                    case 'GroupDetail2':
                        $header = 'Group Detail 2';
                        break;
                    case 'PercentTotal':
                        $header = '%Total';
                        break;
                    case 'PercentUser':
                        $header = '%User';
                        break;
                    default:
                        $header = $key;
                }

                $this->setExcelData($worksheet, $column, 1, $header);
                // Set the border for the header cell
                $styleArray = [
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ];

                $worksheet->getStyle($column . '1')->applyFromArray($styleArray);
            }

            // Loop through the report data and set the values for each column
            foreach ($report as $rowIndex => $rowData) {
                // Set the values for each column in the current row
                foreach ($sortedHeaders as $index => $key) {
                    $column = chr(65 + $index);
                    $value = isset($rowData[$key]) ? $rowData[$key] : '';

                    // Set the value for the current cell
                    $this->setExcelData($worksheet, $column, $rowIndex + 2, $value);
                }
            }

            //End
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            $filename = 'wzandroid0022_'.time().'.xlsx';
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
        $worksheet->getCell($col . $row)->setValueExplicit($data, $type_string);
        $style = $worksheet->getStyle($col . $row);
        $style->getFont()->setName('BIZ UD????')->getColor()->setRGB('000000');
        $style->getFont()->setSize(10)->setBold(false);
    }
}
