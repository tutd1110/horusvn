<?php

namespace App\Http\Controllers\api\Working;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Exceptions\ExclusiveLockException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Project;

use Carbon\Carbon;
use Storage;
use App\Http\Controllers\api\CommonController;
use App\Models\HolidayOffset;

/**
 * Employee API
 *
 * @group Employee
 */
class WorkingTimeController extends Controller
{
    public function getWorkingTime(Request $request)
    {
        try {
            $requestDatas = $request->all();
            $requestDatas['get_user_type_4'] = true;
            $timeSheets = CommonController::getTotalTimesheetReport($requestDatas);
            $users = User::select(
                'users.id',
                'users.fullname',
                'projects.name as project_name',
                'projects.id as project_id',
                DB::raw('sum(task_timings.time_spent) as sum_time')
            )
            ->join('tasks', function ($join) {
                $join->on('users.id', '=', 'tasks.user_id')->whereNull('tasks.deleted_at');
            })
            ->join('task_projects', function ($join) {
                $join->on('tasks.id', '=', 'task_projects.task_id')->whereNull('task_projects.deleted_at');
            })
            ->join('projects', function ($join) {
                $join->on('projects.id', '=', 'task_projects.project_id')->whereNull('projects.deleted_at');
            })
            ->join('task_timings', function ($join) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');
            })
            ->whereBetween('task_timings.work_date', [
                Carbon::parse($requestDatas['start_date'])->format('Y-m-d'),
                Carbon::parse($requestDatas['end_date'])->format('Y-m-d')
            ])
            ->whereNull('task_timings.task_assignment_id')
            ->whereNull('users.deleted_at')
            ->groupBy('users.id', 'users.fullname', 'projects.name', 'projects.id')
            ->orderByRaw('users.date_official asc, users.created_at asc')
            ->get();
        
            $users = $users->groupBy('id')->map(function ($user) {
                $result = [
                    'id' => $user[0]['id'],
                    'fullname' => $user[0]['fullname'],
                    'total_time' => 0,
                ];
            
                foreach ($user as $project) {
                    $result[$project['project_id']] = floatval(number_format($project['sum_time'], 2, '.', ''));
                    $result['total_time'] += $project['sum_time'];
                }
                $result['total_time'] = floatval(number_format($result['total_time'], 2, '.', ''));
                return $result;
            })->values()->all();

            $mergedData = collect($users)->merge($timeSheets)->groupBy(['id'])->map(function ($grouped) {
                $data = [];

                foreach ($grouped as $item) {
                    foreach ($item as $key => $value) {
                        $data[$key] = $value;
                    }
                }

                $data['go_early_sum'] = $data['go_early_sum'] ?? 0;
                $data['leave_late_sum'] = $data['leave_late_sum'] ?? 0;
                $data['extra_warrior_time'] = $data['extra_warrior_time'] ?? 0;
                $data['non_office_time_goouts'] = $data['non_office_time_goouts'] ?? 0;
                $data['origin_workday'] = $data['origin_workday'] ?? 0;
                $data['total_time_working'] = number_format(($data['go_early_sum'] + $data['leave_late_sum'] + $data['extra_warrior_time'] - $data['non_office_time_goouts']) / 3600 + $data['origin_workday'] * 8, 2, '.', '');
                return $data;
            })->values()->all();


            $columnTotals = [];
            $columnPercents = [];
            foreach ($mergedData as $userData) {
                foreach ($userData as $key => $value) {
                    if ($key !== 'id' && $key !== 'fullname' && $key !== 'date_official') {
                        if (!isset($columnTotals[$key])) {
                            $columnTotals[$key] = 0;
                        }
                        $columnTotals[$key] += $value;
                        $columnTotals[$key] = floatval(number_format($columnTotals[$key], 2, '.', ''));
                    }
                }
            }

            foreach ($columnTotals as $key => $total) {
                if ($key !== 'id' && $key !== 'fullname' && $key !== 'date_official' && $key !== 'total_time_working') {
                    if (!isset($columnPercents[$key])) {
                        $columnPercents[$key] = '0 %';
                    }
                    if (is_numeric($total) && $total != 0 && isset($columnTotals['total_time']) && $columnTotals['total_time'] != 0) {
                        $columnPercents[$key] = floatval(number_format( $total / $columnTotals['total_time'] * 100 , 2, '.', '')).' %';

                    } else {
                        $columnPercents[$key] = '0 %';
                    }
                }
            }

            $columnTotals['id'] = 'Total';
            $columnTotals['fullname'] = 'Total';
            $columnPercents['id'] = 'Percent';
            $columnPercents['fullname'] = 'Percent';
            array_unshift($mergedData, $columnPercents);
            array_unshift($mergedData, $columnTotals);

            $projects = Project::select(
                'projects.name as project_name',
                'projects.id as project_id',
                'projects.project_parent_time',
                DB::raw('sum(task_timings.time_spent) as sum_time')
            )
            ->join('task_projects', function ($join) {
                $join->on('projects.id', '=', 'task_projects.project_id')->whereNull('task_projects.deleted_at');
            })
            ->join('tasks', function ($join) {
                $join->on('tasks.id', '=', 'task_projects.task_id')->whereNull('tasks.deleted_at');
            })
            ->join('task_timings', function ($join) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');
            })
            ->whereBetween('task_timings.work_date', [
                Carbon::parse($requestDatas['start_date'])->format('Y-m-d'),
                Carbon::parse($requestDatas['end_date'])->format('Y-m-d')
            ])
            ->whereNull('task_timings.task_assignment_id')
            ->groupBy('projects.name', 'projects.id','projects.project_parent_time')
            ->get();
            if (count($projects) === 0) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                ], Response::HTTP_NOT_FOUND);
            }

            $project_columns = clone $projects;
            
            $totalSumTime = $projects->sum('sum_time');
            $totalProject = [
                'project_id' => 'Total',
                'project_name' => 'Total',
                'sum_time' => floatval(number_format($totalSumTime, 2, '.', '')),
                'percent' => 0,
                'project_salary' => 0,
                'general_expenses' => 0,
                'total_cost' => 0,
                'percent_cost' => 0,
            ];
            $costProject = [
                'project_id' => 9999999,
                'project_name' => 'Chi phí chung',
            ];
            foreach ($projects as $project) {
                $percent = $totalSumTime != 0 ? ($project->sum_time / $totalSumTime) * 100 : 0;
                $project->percent = number_format($percent, 8, '.', '');
                $project->sum_time = floatval(number_format($project->sum_time, 2, '.', ''));

                $totalProject['percent'] += $project->percent;
                $totalProject['percent'] = number_format($totalProject['percent'], 8, '.', '');
            }
            $project_parents = $projects->map(function ($item) {
                return $item->replicate()->setRawAttributes($item->getAttributes());
            });

            $getProjectParent = $this->getProjectParent($project_parents, $totalSumTime);
            $project_parents = $getProjectParent['project_parents'];
            $offsetGeneralExpenses = $getProjectParent['project_offset'];
            

            foreach($project_parents as $key => $value) {
                if (isset($requestDatas['general_expenses'])){
                    $value['general_expenses'] = ($requestDatas['general_expenses'] + $offsetGeneralExpenses) * $value['percent'] / 100;
                    $value['general_expenses'] = floatval(number_format($value['general_expenses'],0,'.',''));
                    $totalProject['general_expenses'] += $value['general_expenses'];
                    $value['total_cost'] = $value['general_expenses'];
                    $totalProject['total_cost'] += $value['total_cost'];
                }
            }
            foreach($project_parents as $key => $value) {
                if (isset($requestDatas['general_expenses'])){
                    $value['percent_cost'] = $value['total_cost'] > 0 ? $value['total_cost']/$totalProject['total_cost']*100 : 0;
                    $value['percent_cost'] = number_format($value['percent_cost'],2,'.','');
                    $totalProject['percent_cost'] += $value['percent_cost'];
                    $totalProject['percent_cost'] = floatval(number_format($totalProject['percent_cost'], 2, '.', ''));
                }
            }
            $projects->prepend((object)$totalProject);

            $totalProject['sum_time'] = $getProjectParent['sum_time'];
            $projectSelect = $project_parents->map(function ($item) {
                return $item->replicate()->setRawAttributes($item->getAttributes());
            });
            $projectSelect->prepend((object)$costProject);
            $project_parents->prepend((object)$totalProject);

            $projects = json_decode(json_encode($projects), true);
            $projectSelect = json_decode(json_encode($projectSelect), true);
            foreach ($projects as $key => $value) {
                $projects[$key]['project_parent_name'] = '';
                foreach ($projectSelect as $key_parent => $value_parent) {
                    if (isset($value['project_parent_time']) && $value['project_parent_time'] == $value_parent['project_id']) {
                        $projects[$key]['project_parent_name'] = $value_parent['project_name'];
                    }
                }
            }

            $data = [
                'users' => $mergedData,
                'project_columns' => $project_columns,
                'projects' => $projects,
                'project_parents' => $project_parents,
                'total_sum_time' => $totalSumTime,
                'project_select' => $projectSelect,
            ];
            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getImportData(Request $request){
        set_time_limit(300);
        ini_set('memory_limit', '2048M');
        if (!$request->file('excel_file')) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-004', ['attribute' => 'File'])
            ], Response::HTTP_NOT_FOUND);
        }
        $file = $request->file('excel_file');
        
        $rows = $this->getDataFromFile($file);
        $dataWork = $this->getWorkingTime($request);
        if (isset($dataWork->original['status']) && $dataWork->original['status'] == 404) {
            return response()->json([
                'status' => $dataWork->original['status'],
                'errors' => $dataWork->original['errors'],
            ], Response::HTTP_NOT_FOUND);
        }
        $mergedData = [];
        foreach ($dataWork->original['users'] as $userData) {
            $nameFromRequest = $userData['fullname'];
            foreach ($rows as $rowFromFile) {
                $nameFromFile = $rowFromFile['fullname'];
                if ($nameFromFile == $nameFromRequest) {
                    $mergedData[] = $rowFromFile + $userData;
                }
            }
        }
        foreach ($mergedData as &$mergedItem) {
            foreach ($mergedItem as $key => &$value) {
                if (is_numeric($key) && $key !== 'total_time' && is_numeric($value) && $mergedItem['total_time'] != 0) {
                    if (isset($mergedItem['salary'])) {
                        $value /= $mergedItem['total_time'];
                        $value *= $mergedItem['salary'];
                    } else {
                        $value = 0;
                    }
                    $columnTotals[$key] = isset($columnTotals[$key]) ? $columnTotals[$key] + $value : $value;
                    $columnTotals[$key] = floatval(number_format($columnTotals[$key], 0, '.', ''));
                    $value = floatval(number_format($value, 0, '.', ''));
                }
            }
            $columnTotals['salary'] = isset($columnTotals['salary']) ? $columnTotals['salary'] + $mergedItem['salary'] : $mergedItem['salary'];
            $mergedItem['salary'] = floatval(number_format($mergedItem['salary'], 0, '.', ''));
        }
        $columnTotals['id'] = 'Total';
        $columnTotals['fullname'] = 'Total';
        array_unshift($mergedData, $columnTotals);
        
        $totalProject = [
            'project_id' => 'Total',
            'project_name' => 'Total',
            'sum_time' => floatval(number_format($dataWork->original['total_sum_time'], 2, '.', '')),
            'percent' => 0,
            'project_salary' => 0,
            'general_expenses' => 0,
            'total_cost' => 0,
            'percent_cost' => 0,
            'general_expenses_entered' => $request->general_expenses ? $request->general_expenses : 0,
        ];
        $costProject = [
            'project_id' => 9999999,
            'project_name' => 'Chi phí chung',
        ];

        foreach($dataWork->original['project_columns'] as $key => $value) {
            foreach ($mergedData[0] as $keyData => $valueData) {
                if ($value['project_id'] == $keyData) {
                    $value['project_salary'] = $valueData;
                }
            }
        }
        $getProjectParent = $this->getProjectParent($dataWork->original['project_columns'], $dataWork->original['total_sum_time']);
        $dataWork->original['project_parents'] = $getProjectParent['project_parents'];
        $offsetGeneralExpenses = $getProjectParent['project_offset'];
        $totalProject['sum_time'] = $getProjectParent['sum_time'];
        if ($request->general_expenses || $offsetGeneralExpenses) {
            $totalGeneralExpenses = $request->general_expenses + $offsetGeneralExpenses;
        }
        foreach($dataWork->original['project_parents'] as $key => $value) {
            if ($request->general_expenses || $request->general_expenses >= 0 || $totalGeneralExpenses){
                $value['general_expenses'] = $totalGeneralExpenses * $value['percent'] / 100;
                $value['general_expenses'] = floatval(number_format($value['general_expenses'],0,'.',''));
                $totalProject['general_expenses'] += $value['general_expenses'];
                
                $value['total_cost'] = $value['general_expenses'] + $value['project_salary']; 
                $totalProject['total_cost'] += $value['total_cost'];
            }
            $totalProject['project_salary'] += $value['project_salary'];
            $totalProject['percent'] += $value['percent'];
            $totalProject['percent'] = number_format($totalProject['percent'], 8, '.', '');
        }

        foreach($dataWork->original['project_parents'] as $key => $value) {
            if ($request->general_expenses || $request->general_expenses >= 0 || $totalGeneralExpenses){
                $value['percent_cost'] = $value['total_cost'] > 0 ? $value['total_cost']/$totalProject['total_cost']*100 : 0;
                $value['percent_cost'] = number_format($value['percent_cost'],2,'.','');
                $totalProject['percent_cost'] += $value['percent_cost'];
                $totalProject['percent_cost'] = floatval(number_format($totalProject['percent_cost'], 2, '.', ''));;
            }
        }
        $projectSelect = $dataWork->original['project_parents']->map(function ($item) {
            return $item->replicate()->setRawAttributes($item->getAttributes());
        }); 
        $projectSelect->prepend((object)$costProject);
        $dataWork->original['project_parents']->prepend((object)$totalProject);
        $data = [
            'users' => $mergedData,
            'project_columns' => $dataWork->original['project_columns'],
            'project_parents' => $dataWork->original['project_parents'],
            'projects' => $dataWork->original['projects'],
            'project_select' => $projectSelect,
        ];
        return response()->json($data);
    }

    private function getDataFromFile($file)
    {
        set_time_limit(300);
        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file);
        //File type check
        if ($inputFileType != "Xlsx") {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => __('MSG-E-016')
            ], Response::HTTP_NOT_FOUND);
        }

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
        $reader->setLoadSheetsOnly('Salary');

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
            $rows[] = [
                'fullname' => $cells[0],
                'salary' => $cells[1],
            ];
        }
        //remove first row that contains label header in file import
        array_splice($rows, 0, 1);

        return $rows;
    }

    public function getProjectParent($project_parents, $totalSumTime){
        
        $project_parents = $project_parents->sortBy('project_parent_time');
        $offsetGeneralExpenses = 0;
        foreach ($project_parents as $key => $value) {
            if ($value->project_parent_time == 9999999) {
                $offsetGeneralExpenses += $value->project_salary;
                $totalSumTime -= $value->sum_time;
                $project_parents = $project_parents->reject(function ($item) {
                    return $item->project_parent_time == 9999999;
                });
            } 
        }
        foreach ($project_parents as $key => $value) {
            $parentProjects = $project_parents->where('project_parent_time', $value->project_id)->all();
            $sumTimeChildren = 0;

            foreach ($parentProjects as $parentProject) {
                if ($parentProject->project_id != $parentProject->project_parent_time) {
                    $sumTimeChildren += $parentProject->sum_time;
                    $value->project_salary += floatval(number_format($parentProject->project_salary, 2, '.', ''));
                    $project_parents = $project_parents->reject(function ($item) use ($parentProject) {
                        return $item->project_id == $parentProject->project_id;
                    });
                }
            }

            $value->sum_time += $sumTimeChildren;
            $percent_parent = ($value->sum_time / $totalSumTime) * 100;
            $value->percent = number_format($percent_parent, 8, '.', '');
            
        }
        $project_parents = $project_parents->reject(function ($item) {
            return $item->project_id == 9999999;
        });
        $results = [
            'project_parents' => $project_parents,
            'project_offset' => $offsetGeneralExpenses,
            'sum_time' => $totalSumTime
        ];
    
        return $results;
    }
    public function getTemplateSalary()
    {
        $filePath = resource_path('templates/payroll_template.xlsx');
        if (file_exists($filePath)) {
            return response()->download($filePath, 'payroll_template.xlsx');
        } else {
            return response()->json(['error' => 'File not found.'], 404);
        }
    }

    public function export(Request $request)
    {
        try {
            $requestDatas = $request->all();
            $templatePath = resource_path('templates/working_time_template.xlsx');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
            //set value
            $response = $this->getImportData($request);
            if (isset($response->original['status']) && $response->original['status'] == 404) {
                return response()->json([
                    'status' => $response->original['status'],
                    'errors' => $response->original['errors'],
                ], Response::HTTP_NOT_FOUND);
            }
            $data = $response->original;
            $this->fillData($spreadsheet, $data);
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            $filename = 'working_time_'.time().'.xlsx';
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

    private function fillData($spreadsheet, $data)
    {
        $columns = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
        ];
        $row = 2;

        $worksheet1 = $spreadsheet->getSheetByName('chi_phi_luong');
        $worksheet2 = $spreadsheet->getSheetByName('ti_le_du_an');
        $worksheet3 = $spreadsheet->getSheetByName('thong_ke');

        $data = json_decode(json_encode($data), true);
        foreach ($data['projects'] as $key => $value) {
            $this->setExcelData($worksheet2, 'A', $key+$row, $key+1);
            $this->setExcelData($worksheet2, 'B', $key+$row, $value['project_name']);
            $this->setExcelData($worksheet2, 'C', $key+$row, $value['sum_time'], 'n');
            $this->setExcelData($worksheet2, 'D', $key+$row, $value['percent'].'%');
            $this->setExcelData($worksheet2, 'E', $key+$row, isset($value['project_parent_name']) ? $value['project_parent_name'] : '');
        }

        foreach ($data['project_parents'] as $key => $value) {
            $this->setExcelData($worksheet3, 'H', 1, $data['project_parents'][0]['general_expenses_entered']);
            $this->setExcelData($worksheet3, 'A', $key+$row+1, $key+1);
            $this->setExcelData($worksheet3, 'B', $key+$row+1, $value['project_name']);
            $this->setExcelData($worksheet3, 'C', $key+$row+1, $value['sum_time'], 'n');
            $this->setExcelData($worksheet3, 'D', $key+$row+1, $value['percent'].'%');
            $this->setExcelData($worksheet3, 'E', $key+$row+1, $value['project_salary'], 'n');
            $this->setExcelData($worksheet3, 'F', $key+$row+1, $value['general_expenses'], 'n');
            $this->setExcelData($worksheet3, 'G', $key+$row+1, $value['total_cost'], 'n');
            $this->setExcelData($worksheet3, 'H', $key+$row+1, $value['percent_cost'].'%');
        }
        foreach ($data['users'] as $key => $value) {
            foreach ($data['project_columns'] as $keyData => $columnData) {
                $this->setExcelData($worksheet1, $columns[$keyData+2], 1, $columnData['project_name']);
                $data['project_columns'][$keyData]['col'] = $columns[$keyData+2];
                $endCol = $columns[$keyData+3];
            }
            $this->setExcelData($worksheet1, $endCol, 1, 'Tổng lương');
            $this->setExcelData($worksheet1, 'A', $key+$row, $key+1);
            $this->setExcelData($worksheet1, 'B', $key+$row, $value['fullname']);
            foreach ($data['project_columns'] as $keyData => $columnData) {
                if (isset($value[$columnData['project_id']])) {
                    $this->setExcelData($worksheet1, $columnData['col'], $key+$row, $value[$columnData['project_id']], 'n');
                }
            }
            $this->setExcelData($worksheet1, $endCol, $key+$row, $value['salary'], 'n');
        }
    }

    private function setExcelData($worksheet, $col, $row, $data, $type_string = "s",$size=10,$bold=false)
    {
        $worksheet->getCell($col . $row)->setValueExplicit($data, $type_string);
        $style = $worksheet->getStyle($col . $row);
        $style->getFont()->setName('Calibri')->getColor()->setRGB('000000');
        $style->getFont()->setSize($size)->setBold($bold);
    }

}
