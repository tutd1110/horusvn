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
use File;
use Cache;

/**
 * WZAndroid0022 Import API
 *
 * @group WZAndroid0022 Import
 */
class WZAndroid0022ImportController extends Controller
{
    protected $cacheIncome = 'wzandroid0022_data_income';
    protected $cacheOutcome = 'wzandroid0022_data_outcome';
    protected $cacheApi = 'url_api';
    protected $cacheFileName = 'in/outcome_file';

    public function import(Request $request)
    {
        try {
            $requestDatas = $request->all();

            if (!$request->file('excel_file')) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => __('MSG-E-004', ['attribute' => 'File'])
                ], Response::HTTP_NOT_FOUND);
            }

            $file = $request->file('excel_file');
            // Check if the file already exists
            $filePath = resource_path('templates/WZAndroid0022.xlsx');
            if (File::exists($filePath)) {
                // If the file exists, delete it
                File::delete($filePath);
            }

            $this->transformDataAndCache($file, "Data Income", $this->cacheIncome);
            $this->transformDataAndCache($file, "Data Outcome", $this->cacheOutcome);
            $this->transformApiAndCache($file, "API", $this->cacheApi);

            // Save the file to the templates directory with a new name
            File::put($filePath, file_get_contents($file));

            //restore cache filename
            Cache::forget($this->cacheFileName);
            $cacheData = [
                'filename' => Auth()->user()->fullname.'_'.$file->getClientOriginalName(),
            ];
            Cache::forever($this->cacheFileName, $cacheData);

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    private function getDataFromFile($file, $sheetName)
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
        $reader->setLoadSheetsOnly($sheetName);

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

    private function transformDataAndCache($file, $sheetName, $cacheKey)
    {
        Cache::forget($cacheKey);

        $rows = $this->getDataFromFile($file, $sheetName);

        $data = collect($rows)->filter(function ($row) {
            return !is_null($row[1]);
        })->map(function ($row) {

            return [
                'id' => $row[1],
                'name' => $row[4],
                'method' => $row[5],
                'group' => $row[6],
                'group_detail_1' => $row[7],
                'group_detail_2' => $row[8],
            ];
        });

        Cache::forever($cacheKey, $data);
    }

    private function transformApiAndCache($file, $sheetName, $cacheKey)
    {
        Cache::forget($cacheKey);

        $rows = $this->getDataFromFile($file, $sheetName);

        $data = collect($rows)->filter(function ($row) {
            return !is_null($row[1]);
        })->map(function ($row) {

            return [
                'name' => $row[0],
                'url' => $row[1]
            ];
        });

        Cache::forever($cacheKey, $data);
    }
}
