<?php

namespace App\Http\Controllers\api\Export;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api\CommonController;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Models\Sticker;
use App\Models\Project;
use App\Models\Priority;
use Carbon\Carbon;
use Storage;
use File;

/**
 * Task Assignment Export API
 *
 * @group Task Assignment Export
 */
class TaskAssignmentExportController extends Controller
{
    public function export(Request $request)
    {
        try {
            set_time_limit(300);
            ini_set('memory_limit', '2048M');

            if (empty($request->all())) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-I-008')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            $tasks = $this->getTaskAssignments($request);

            //no search results
            if (count($tasks) == 0) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            //Load template excel
            $template = resource_path('templates/task_assignment_template.xlsx');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($template);
            //set value
            $worksheet = $spreadsheet->getActiveSheet();
            $worksheet->setTitle('Bugs');

            // Create a new worksheet called "List dropdown data"
            $newWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'List');
            $spreadsheet->addSheet($newWorkSheet, 0);

            $projects = Project::select('id', 'name')->orderBy('ordinal_number', 'asc')->get();
            //list users by department with job
            $departments = config('const.departments_with_job');
            $dpIds = [];
            foreach ($departments as $item) {
                $dpIds[] = $item['value'];
            }
            $users = User::select('id', 'fullname')
                            ->whereIn('department_id', $dpIds)->get();

            $levels = config('const.task_assignments_level');

            $tagTests = config('const.task_assignments_tag_test');

            $types = config('const.task_assignment_type');
            
            //status
            $status = config('const.task_assignments_status');

            $worksheet1 = $spreadsheet->getSheetByName('List');

            foreach ($users as $key1 => $user) {
                $this->setExcelData($worksheet1, "A", $key1+1, $user->fullname);
            }

            foreach ($projects as $key2 => $project) {
                $this->setExcelData($worksheet1, "E", $key2+1, $project->name);
            }

            $dpNames = [];
            foreach ($departments as $key3 => $department) {
                $this->setExcelData($worksheet1, "I", $key3+1, $department['label']);

                $dpNames[$department['value']] = $department['label'];
            }

            $levelName = [];
            foreach ($levels as $key4 => $level) {
                $this->setExcelData($worksheet1, "M", $key4+1, $level['label']);

                $levelName[$level['value']] = $level['label'];
            }

            $statusName = [];
            foreach ($status as $key5 => $item) {
                $this->setExcelData($worksheet1, "O", $key5+1, $item['label']);

                $statusName[$item['value']] = $item['label'];
            }

            $tagTestName = [];
            foreach ($tagTests as $key6 => $tag) {
                $this->setExcelData($worksheet1, "Q", $key6+1, $tag['label']);

                $tagTestName[$tag['value']] = $tag['label'];
            }

            $typeName = [];
            foreach ($types as $key7 => $type) {
                $this->setExcelData($worksheet1, "S", $key7+1, $type['label']);

                $typeName[$type['value']] = $type['label'];
            }

            $this->fillDataExcel(
                $tasks,
                $worksheet,
                2,
                count($projects),
                $dpNames,
                count($users),
                $levelName,
                $statusName,
                $tagTestName,
                $typeName
            );

            //End
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            $filename = 'horus_'.time().'.xlsx';
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

    private function fillDataExcel(
        $tasks,
        $worksheet,
        $row,
        $countProject,
        $dpNames,
        $countUser,
        $levelName,
        $statusName,
        $tagTestName,
        $typeName
    ) {
        foreach ($tasks as $key => $value) {
            //set task assignments id
            $this->setExcelData($worksheet, 'A', $key+$row, $value->id);

            //set task assignments start_date
            $this->setExcelData($worksheet, 'B', $key+$row, $value->start_date);

            //set task assignments tester
            $this->setDropdownList($worksheet, 'C', $key+$row, $countUser, 'A', $value->tester_name);

            //set task assignments project
            $this->setDropdownList($worksheet, 'D', $key+$row, $countProject, 'E', $value->project_name);

            //set task assignments department
            $this->setDropdownList(
                $worksheet,
                'E',
                $key+$row,
                $dpNames,
                'I',
                $value->assigned_department_id ? $dpNames[$value->assigned_department_id] : ""
            );

            //set task assignments tasks name
            $this->setExcelData($worksheet, 'F', $key+$row, $value->task_name);

            //set task assignments tasks id
            $this->setExcelData($worksheet, 'G', $key+$row, $value->task_id);

            //set task assignments level
            $this->setDropdownList(
                $worksheet,
                'H',
                $key+$row,
                $levelName,
                'M',
                $value->level ? $levelName[$value->level] : ""
            );

            //set task assignments description
            $this->setExcelData($worksheet, 'I', $key+$row, $value->description);

            //set task assignments fixer
            $this->setDropdownList($worksheet, 'J', $key+$row, $countUser, 'A', $value->fixer_name);

            //set task assignments status
            $this->setDropdownList(
                $worksheet,
                'K',
                $key+$row,
                $statusName,
                'O',
                $value->status ? $statusName[$value->status] : ""
            );

            //set task assignments tag test
            $this->setDropdownList(
                $worksheet,
                'L',
                $key+$row,
                $tagTestName,
                'Q',
                $value->tag_test ? $tagTestName[$value->tag_test] : ""
            );

            //set task assignments type
            $this->setDropdownList(
                $worksheet,
                'M',
                $key+$row,
                $typeName,
                'S',
                $value->type ? $typeName[$value->type] : ""
            );
            //set task weight
            $this->setExcelData($worksheet, 'N', $key+$row, $value->weight);
            //set user_created
            $this->setExcelData($worksheet, 'O', $key+$row, $value->user_created);
        }
    }

    private function setExcelData($worksheet, $col, $row, $data, $type_string = "s")
    {
        $worksheet->getCell($col . $row)->setValueExplicit($data, $type_string);
        $style = $worksheet->getStyle($col . $row);
        $style->getFont()->setName('BIZ UD????')->getColor()->setRGB('000000');
        $style->getFont()->setSize(10)->setBold(false);
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

    private function getTaskAssignments($request)
    {
        $query = TaskAssignment::leftJoin('users as testers', function ($join) {
            $join->on('testers.id', '=', 'task_assignments.tester_id')->whereNull('testers.deleted_at');
        })
        ->leftJoin('users as fixers', function ($join) {
            $join->on('fixers.id', '=', 'task_assignments.assigned_user_id')->whereNull('fixers.deleted_at');
        })
        ->leftJoin('projects', function ($join) {
            $join->on('projects.id', '=', 'task_assignments.project_id')->whereNull('projects.deleted_at');
        })
        ->leftJoin('tasks', function ($join) {
            $join->on('tasks.id', '=', 'task_assignments.task_id')->whereNull('tasks.deleted_at');
        })
        ->leftJoin('users as user_create', function ($join) {
            $join->on('user_create.id', '=', 'tasks.user_id')->whereNull('user_create.deleted_at');
        })
        ->leftjoin('task_timings', function ($join) {
            $join->on('task_timings.task_assignment_id', '=', 'task_assignments.id')->whereNull('task_timings.deleted_at');
        })
        ->select(
            'task_assignments.id as id',
            'testers.fullname as tester_name',
            'projects.name as project_name',
            'task_assignments.task_id as task_id',
            'tasks.name as task_name',
            'task_assignments.start_date as start_date',
            'task_assignments.description as description',
            'task_assignments.assigned_department_id as assigned_department_id',
            'fixers.fullname as fixer_name',
            'task_assignments.status as status',
            'task_assignments.tag_test as tag_test',
            'task_assignments.level as level',
            'task_assignments.type as type',
            'task_assignments.note as note',
            'task_timings.weight as weight',
            'user_create.fullname as user_created',
        );

        //Add SQL according to requested search conditions
        //On request
        $requestDatas = $request->all();
        if (!empty($requestDatas)) {
            //get json from request
            $query = $this->addSqlWithSorting($requestDatas, $query);
        }

        $tasks = $query->get();

        return $tasks;
    }

    private function addSqlWithSorting($requestDatas, $query)
    {
        $query = CommonController::applyTaskFilters($query, $requestDatas);

        return $query;
    }
}
