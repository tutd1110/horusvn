<?php

namespace App\Http\Controllers\api\Export;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Sticker;
use Storage;

/**
 * Sticker Export API
 *
 * @group Sticker Export
 */
class StickerExportController extends Controller
{
    public function export(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();

            $query = Sticker::query()
                ->select(
                    'id',
                    'ordinal_number',
                    'name',
                    'department_id',
                    'level_1',
                    'level_2',
                    'level_3',
                    'level_4',
                    'level_5',
                    'level_6',
                    'level_7',
                    'level_8',
                    'level_9',
                    'level_10',
                )
                ->where(function ($query) use ($requestDatas) {
                    if (isset($requestDatas['department_id']) && !empty($requestDatas['department_id'])) {
                        $query->where('department_id', $requestDatas['department_id']);
                    }
                });

            $stickers = $query->orderBy('ordinal_number', 'asc')->get();

            //Load template excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(resource_path('templates/sticker_template.xlsx'));
            //set value
            $worksheet = $spreadsheet->getActiveSheet();
            $worksheet->setTitle('Stickers');
            // Create a new worksheet called "List dropdown data"
            $newWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'List');
            $spreadsheet->addSheet($newWorkSheet, 0);

            $worksheet1 = $spreadsheet->getSheetByName('List');
            //list users by department with job
            $departments = config('const.departments_with_job');
            $dpNames = [];
            foreach ($departments as $key1 => $department) {
                $this->setExcelData($worksheet1, "A", $key1+1, $department['label']);

                $dpNames[$department['value']] = $department['label'];
            }

            //action
            $this->setExcelData($worksheet1, "D", 2, "Delete");

            $this->fillDataExcel($worksheet, $stickers, $dpNames);

            //End
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            $filename = 'stickers_'.time().'.xlsx';
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

    private function fillDataExcel($worksheet, $stickers, $dpNames)
    {
        $row = 2;
        foreach ($stickers as $key => $value) {
            //ID
            $this->setExcelData($worksheet, 'A', $key+$row, $value->id);
            //STT
            $this->setExcelData($worksheet, 'B', $key+$row, $value->ordinal_number);
            //Name
            $this->setExcelData($worksheet, 'C', $key+$row, $value->name);
            //Department
            $this->setDropdownList(
                $worksheet,
                'D',
                $key+$row,
                $dpNames,
                'A',
                $value->department_id ? $dpNames[$value->department_id] : ""
            );
            //Level_1
            $this->setExcelData($worksheet, 'E', $key+$row, $value->level_1);
            //Level_2
            $this->setExcelData($worksheet, 'F', $key+$row, $value->level_2);
            //Level_3
            $this->setExcelData($worksheet, 'G', $key+$row, $value->level_3);
            //Level_4
            $this->setExcelData($worksheet, 'H', $key+$row, $value->level_4);
            //Level_5
            $this->setExcelData($worksheet, 'I', $key+$row, $value->level_5);
            //Level_6
            $this->setExcelData($worksheet, 'J', $key+$row, $value->level_6);
            //Level_7
            $this->setExcelData($worksheet, 'K', $key+$row, $value->level_7);
            //Level_8
            $this->setExcelData($worksheet, 'L', $key+$row, $value->level_8);
            //Level_9
            $this->setExcelData($worksheet, 'M', $key+$row, $value->level_9);
            //Level_10
            $this->setExcelData($worksheet, 'N', $key+$row, $value->level_10);
            //Action
            $this->setDropdownList(
                $worksheet,
                'O',
                $key+$row,
                2,
                'D',
                ""
            );
        }
    }

    private function setDropdownList($worksheet, $col, $row, $totalRow, $dropdownCol, $value)
    {
        $cell = $col.$row;
        $validation = $worksheet->getCell($cell)->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Input error');
        $validation->setError('Value is not in list.');
        $validation->setPromptTitle('Pick from list');
        $validation->setPrompt('Please pick a value from the drop-down list.');

        $highestCell = $totalRow;
        if (is_array($totalRow)) {
            $highestCell = count($totalRow);
        }

        $rangeDropdown = "$".$dropdownCol."$1:$".$dropdownCol."$".$highestCell;
        $validation->setFormula1('\'List\'!'.$rangeDropdown);

        $this->setExcelData($worksheet, $col, $row, $value);
    }

    private function setExcelData($worksheet, $col, $row, $data, $type_string = "s")
    {
        $worksheet->getCell($col . $row)->setValueExplicit($data, $type_string);
        $style = $worksheet->getStyle($col . $row);
        $style->getFont()->setName('BIZ UD????')->getColor()->setRGB('000000');
        $style->getFont()->setSize(10)->setBold(false);
    }
}
