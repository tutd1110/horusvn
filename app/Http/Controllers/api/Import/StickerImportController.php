<?php

namespace App\Http\Controllers\api\Import;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api\CommonController;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Sticker;
use Storage;

/**
 * Sticker Import API
 *
 * @group Sticker Import
 */
class StickerImportController extends Controller
{
    public function import(Request $request)
    {
        try {
            //on request
            $requestDatas = $request->all();
            if (!$request->file('excel_file')) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => __('MSG-E-004', ['attribute' => 'File'])
                ], Response::HTTP_NOT_FOUND);
            }

            $file = $request->file('excel_file');

            $rows = $this->getDataFromFile($file);

            $model = new Sticker();
            $connectionName = $model->getConnectionName();

            //start transaction control
            DB::connection($connectionName)->beginTransaction();

            $message = "";
            foreach ($rows as $key => $row) {
                $departmentId = "";
                $sticker = null;
                //check id is exist or not in stickers table
                if ($row[0]) {
                    $sticker = Sticker::where('id', $row[0])->first();

                    if (!$sticker) {
                        $message = "Line ".($key+2).": ID không tồn tại trên hệ thống!";
                        break;
                    }

                    // check if row should be deleted
                    if ($row[14] === 'Delete') {
                        $sticker->delete();
                        CommonController::clearTaskWeight($sticker->id);

                        continue;
                    }
                }

                //check department is exist or not
                if ($row[3]) {
                    $departmentId = $this->getDepartmentIdImport($row[3]);

                    if (!$departmentId) {
                        $message = "Line ".($key+2).": Bộ phận không tồn tại trên hệ thống!";
                        break;
                    }
                }

                if ($sticker) {
                    $sticker->ordinal_number = $row[1];
                    $sticker->name = $row[2];
                    $sticker->department_id = $departmentId;

                    // Set level columns
                    for ($i = 4; $i <= 13; $i++) {
                        $columnName = "level_" . ($i - 3);
                        $sticker->$columnName = $row[$i];

                        if ($sticker->isDirty($columnName)) {
                            CommonController::updateTaskWeight($sticker->id, $i - 3, $sticker->$columnName);
                        }
                    }

                    $sticker->save();
                } else {
                    Sticker::create([
                        'ordinal_number' => $row[1],
                        'name' => $row[2],
                        'department_id' => $departmentId,
                        'level_1' => $row[4],
                        'level_2' => $row[5],
                        'level_3' => $row[6],
                        'level_4' => $row[7],
                        'level_5' => $row[8],
                        'level_6' => $row[9],
                        'level_7' => $row[10],
                        'level_8' => $row[11],
                        'level_9' => $row[12],
                        'level_10' => $row[13]
                    ]);
                }
            }

            if ($message) {
                DB::connection($connectionName)->rollBack();

                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $message,
                    ], Response::HTTP_NOT_FOUND);
            }

            DB::connection($connectionName)->commit();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            DB::connection($connectionName)->rollBack();
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    private function getDataFromFile($file)
    {
        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file);
        //File type check
        if ($inputFileType != "Xlsx") {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-016')
            ], Response::HTTP_NOT_FOUND);
        }

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
        $reader->setLoadSheetsOnly('Stickers');

        $spreadsheet = $reader->load($file);
        
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false); // This loops through all cells,
            $cells = [];
            foreach ($cellIterator as $cell) {
                $cells[] = $cell->getValue();
            }
            $rows[] = $cells;
        }

        //remove first row that contains label header in file import
        array_splice($rows, 0, 1);

        return $rows;
    }

    private function getDepartmentIdImport($value)
    {
        $id = null;
        $constDepartments = config('const.departments_with_job');

        //Filter the array based on the label
        $filtered = array_filter($constDepartments, function ($item) use ($value) {
            return $item["label"] == $value;
        });

        //Get the first matching element
        $firstMatch = reset($filtered);

        //Extract the 'value' field from the first matching element
        $id = $firstMatch ? $firstMatch["value"] : null;

        return $id;
    }
}
