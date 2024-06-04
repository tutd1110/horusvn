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
use App\Models\User;
use App\Models\Sticker;
use App\Models\Project;
use App\Models\Priority;
use Carbon\Carbon;
use Storage;
use File;

/**
 * Task Export API
 *
 * @group Task Export
 */
class TaskExportController extends Controller
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

            $tasks = $this->getTasksBySqlSelectRaw($request);

            //no search results
            if (!$tasks) {
                return response()->json(
                    ['status' => Response::HTTP_NOT_FOUND,
                    'errors' => __('MSG-E-003')
                    ],
                    Response::HTTP_NOT_FOUND
                );
            }

            //task's name
            $range = ['B','C','D','E','F','G','H'];
            //Load template excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(resource_path('templates/task_template.xlsx'));
            //set value
            $worksheet = $spreadsheet->getActiveSheet();
            $worksheet->setTitle('Tasks');

            // foreach ($worksheet->getColumnIterator() as $column) {
            //     $worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            // }

            // Create a new worksheet called "List dropdown data"
            $newWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'List');
            $spreadsheet->addSheet($newWorkSheet, 0);

            $projects = Project::select('id', 'name')->orderBy('ordinal_number', 'asc')->get();
            //list users by department with job
            $departments = CommonController::getDepartmentsJob();
            $dpIds = array_map(function ($department) {
                return $department['value'];
            }, $departments);
            $users = User::select('id', 'fullname')
                            ->whereIn('department_id', $dpIds)->get();
            //stickers
            $stickers = Sticker::select('id', 'name')->get();
            //priorities
            $priorities = Priority::select('id', 'label')->get();
            //status
            $status = config('const.status');

            $worksheet1 = $spreadsheet->getSheetByName('List');
            foreach ($projects as $key => $project) {
                $this->setExcelData($worksheet1, "A", $key+1, $project->name);
            }
            $dpNames = [];
            foreach ($departments as $key1 => $department) {
                $this->setExcelData($worksheet1, "D", $key1+1, $department['label']);

                $dpNames[$department['value']] = $department['label'];
            }
            foreach ($users as $key2 => $user) {
                $this->setExcelData($worksheet1, "F", $key2+1, $user->fullname);
            }
            foreach ($stickers as $key3 => $sticker) {
                $this->setExcelData($worksheet1, "J", $key3+1, $sticker->name);
            }
            foreach ($priorities as $key4 => $priority) {
                $this->setExcelData($worksheet1, "L", $key4+1, $priority->label);
            }
            $statusName = [];
            foreach ($status as $key5 => $item) {
                $this->setExcelData($worksheet1, "N", $key5+1, $item['label']);

                $statusName[$item['value']] = $item['label'];
            }
            //action
            $actions = config('const.type_delete_task');

            foreach ($actions as $key6 => $action) {
                $this->setExcelData($worksheet1, "Q", $key6+1, $action);
            }

            $this->loopHierarchicalTree(
                $tasks,
                $worksheet,
                2,
                [],
                $range,
                0,
                count($projects),
                $dpNames,
                count($users),
                count($stickers),
                count($priorities),
                $statusName,
                count($actions)
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
            if (count($tasks) > 0) {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => 'Task with ID: ('.$tasks[0]->id.') has more than 7 level that can not be exported',
                    ], Response::HTTP_NOT_FOUND);
            } else {
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
            }
        }
    }

    private function loopHierarchicalTree(
        $tasks,
        $worksheet,
        $row,
        $parentChildColArr,
        $range,
        $rangeIndex,
        $countProject,
        $dpNames,
        $countUser,
        $countSticker,
        $countPriority,
        $statusName,
        $countAction
    ) {
        $countRow = $row;
        $arrayCol = $parentChildColArr;
        foreach ($tasks as $key => $value) {
            if (is_array($value->grandchildren)) {
                if (!$value->task_parent) {
                    $rangeIndex = 0;
                }
                $arrayCol[$value->id] = $range[$rangeIndex];

                if (isset($arrayCol[$value->task_parent])) {
                    $colKey = array_search($arrayCol[$value->task_parent], $range);
                    $rangeIndex = $colKey + 1;

                    $arrayCol[$value->id] = $range[$rangeIndex];
                }

                $array = $this->fillData(
                    $worksheet,
                    $range,
                    $rangeIndex,
                    $countRow,
                    $value,
                    $countProject,
                    $dpNames,
                    $countSticker,
                    $countPriority,
                    $countUser,
                    $statusName,
                    $countAction
                );

                $countRow++;
                $rangeIndex++;

                $tmp = $this->loopHierarchicalTree(
                    $value->grandchildren,
                    $worksheet,
                    $countRow,
                    $arrayCol,
                    $range,
                    $rangeIndex,
                    $countProject,
                    $dpNames,
                    $countUser,
                    $countSticker,
                    $countPriority,
                    $statusName,
                    $countAction
                );
                $countRow = $tmp['next_row'];
                $arrayCol = $tmp['array_col_parent_child'];
            } elseif (!$value->grandchildren && $value->task_parent) {
                $colKey = array_search($arrayCol[$value->task_parent], $range);
                $rangeIndex = $colKey + 1;

                $array = $this->fillData(
                    $worksheet,
                    $range,
                    $rangeIndex,
                    $countRow,
                    $value,
                    $countProject,
                    $dpNames,
                    $countSticker,
                    $countPriority,
                    $countUser,
                    $statusName,
                    $countAction
                );

                $countRow++;
            } elseif (!$value->grandchildren && !$value->task_parent) {
                $rangeIndex = 0;
                $array = $this->fillData(
                    $worksheet,
                    $range,
                    $rangeIndex,
                    $countRow,
                    $value,
                    $countProject,
                    $dpNames,
                    $countSticker,
                    $countPriority,
                    $countUser,
                    $statusName,
                    $countAction
                );
                
                $countRow++;
            }
        }

        return [
            'next_row' => $countRow,
            'array_col_parent_child' => $parentChildColArr
        ];
    }

    private function fillData(
        $worksheet,
        $range,
        $rangeIndex,
        $row,
        $value,
        $countProject,
        $dpNames,
        $countSticker,
        $countPriority,
        $countUser,
        $statusName,
        $countAction
    ) {
        //set task's name
        $this->setExcelData($worksheet, "A", $row, $value->id);
        $this->setExcelData($worksheet, $range[$rangeIndex], $row, $value->name);

        //set task's project
        $this->setDropdownList($worksheet, 'I', $row, $countProject, 'A', $value->project_names);
        //set task's department
        $this->setDropdownList(
            $worksheet,
            'J',
            $row,
            $dpNames,
            'D',
            isset($dpNames[$value->department_id]) ? $dpNames[$value->department_id] : $value->department_id
        );
        //set task's stciker
        $this->setDropdownList(
            $worksheet,
            'K',
            $row,
            $countSticker,
            'J',
            $value->sticker_name
        );
        //set task's priority
        $this->setDropdownList(
            $worksheet,
            'L',
            $row,
            $countPriority,
            'L',
            $value->priority_name
        );
        //set task's weight
        $this->setExcelData($worksheet, "M", $row, $value->weight);
        //set task's user
        $this->setDropdownList(
            $worksheet,
            'N',
            $row,
            $countUser,
            'F',
            $value->fullname
        );
        //set task's start_time
        $this->setExcelData(
            $worksheet,
            "O",
            $row,
            $value->start_time ? Carbon::create($value->start_time)->format('d/m/Y') : ""
        );
        //set task's end_time
        $this->setExcelData(
            $worksheet,
            "P",
            $row,
            $value->end_time ? Carbon::create($value->end_time)->format('d/m/Y') : ""
        );
        //set task's estimate time
        $this->setExcelData($worksheet, "Q", $row, $value->total_estimate_time);
        //set task's actual time
        $this->setExcelData($worksheet, "R", $row, $value->total_time_spent);
        //set task's progress
        $this->setExcelData($worksheet, "S", $row, $value->progress);
        $this->setExcelData($worksheet, "V", $row, $value->deadline);
        //set task's department
        $this->setDropdownList(
            $worksheet,
            'T',
            $row,
            $statusName,
            'N',
            $value->status ? $statusName[$value->status] : ""
        );

        //set task's department
        $this->setDropdownList(
            $worksheet,
            'U',
            $row,
            $countAction,
            'Q',
            ""
        );

        return [
            'row' => $row,
            'range_index' => $rangeIndex
        ];
    }

    private function setDropdownList($worksheet, $col, $row, $totalRow, $dropdownCol, $value)
    {
        if ($col === 'I') {
            $value = str_replace(['{', '}', '"'], '', $value);
        }

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

    /** Append SQL Raw if json is requested
     *
     * @param  $request
     * @return $tasks
    */
    private function getTasksBySqlSelectRaw($request)
    {
        //On request
        $requestDatas = $request->all();

        $filteredData = array_filter($requestDatas);
        $isOnlySearchIds = false;
        $stringSearchIds = isset($requestDatas['ids']) && count($requestDatas['ids']) > 0
            ? implode(',', $requestDatas['ids']) : null;
        $tree = [];

        //sql join parent and child
        if (count($filteredData) === 1 && isset($filteredData['ids'])) {
            $isOnlySearchIds = true;
            $sqlRaw = " JOIN parent parent ON parent.id = child.task_parent";

            $result = $this->recursiveSql($sqlRaw, $stringSearchIds, $requestDatas, $isOnlySearchIds);
        } else {
            //get list tasks id
            $query = Task::join('task_timings', function ($join) use ($requestDatas) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');

                if (!empty($requestDatas['start_time']) && !empty($requestDatas['end_time'])) {
                    $join->whereDate(
                        'task_timings.work_date',
                        '>=',
                        Carbon::create($requestDatas['start_time'])->format('Y/m/d')
                    );
                    $join->whereDate(
                        'task_timings.work_date',
                        '<=',
                        Carbon::create($requestDatas['end_time'])->format('Y/m/d')
                    );
                }
            })
            ->leftJoin('task_projects', function ($join) use ($requestDatas) {
                $join->on('task_projects.task_id', '=', 'tasks.id')->whereNull('task_projects.deleted_at');

                if (isset($requestDatas['project_id']) && count($requestDatas['project_id']) > 0) {
                    $projectIds = array_filter($requestDatas['project_id']);

                    $join->where(function ($groupQuery) use ($requestDatas, $projectIds) {
                        if (!empty($projectIds)) {
                            $groupQuery->whereIn('task_projects.project_id', $projectIds);
                        }

                        if (in_array(0, $requestDatas['project_id'])) {
                            $groupQuery->orWhereNull('task_projects.project_id');
                        }
                    });
                }
            })
            ->select('tasks.id');
            //Add SQL according to requested search conditions
            if (!empty($requestDatas)) {
                //get json from request
                $query = $this->addSqlWithSorting($requestDatas, $query);
            }
            $parentIds = $query->get();

            if (count($parentIds->toArray()) == 0) {
                return null;
            }

            $listSearchIds = $parentIds->toArray();
            $listSearchIds = array_unique($listSearchIds, SORT_REGULAR);

            $stringIds = implode(",", array_column($listSearchIds, 'id'));
            
            $sqlRaw = " JOIN parent parent ON parent.task_parent = child.id";

            $result = $this->recursiveSql($sqlRaw, $stringIds, $requestDatas, $isOnlySearchIds, $stringSearchIds);
        }

        if (isset($requestDatas['ids']) && count($requestDatas['ids']) > 0) {
            $originParentIds = [];
            $parentIds = array_column($result, 'id');
            
            //assign task_parent is null to createHierarchicalTree if the task_parent is not exist in parentIds
            foreach ($result as $item) {
                if (!in_array($item->task_parent, $parentIds)) {
                    $originParentIds[$item->id] = $item->task_parent;

                    $item->task_parent = null;
                }
            }

            //create hierarchical tree data
            $tree = $this->createHierarchicalTree($result);

            //assign again task_parent for the records that were assigned task_parent is null above
            if (is_array($tree)) {
                foreach ($tree as $node) {
                    if (isset($originParentIds[$node->id])) {
                        $node->task_parent = $originParentIds[$node->id];
                    }
                }
            }
        } else {
            //create hierarchical tree data
            $tree = $this->createHierarchicalTree($result);
        }

        return $tree;
    }

    private function recursiveSql($sqlJoinRaw, $stringIds, $requestDatas, $isOnlySearchIds, $stringSearchIds = null)
    {
        //get list tasks by list parent id
        $sql = "";
        $sql .= "WITH RECURSIVE parent AS (";

        $sql .= "SELECT";
        $sql .= " tasks.id, tasks.name, tasks.sticker_id, tasks.task_parent, tasks.deadline,";
        $sql .= " tasks.priority, tasks.weight,";
        $sql .= " tasks.department_id, tasks.user_id, tasks.progress,";
        $sql .= " tasks.status";
        $sql .= " FROM tasks";
        $sql .= " WHERE tasks.id IN (".$stringIds.")";
        $sql .= " AND tasks.deleted_at is null";

        $sql .= " UNION ";

        $sql .= " SELECT";
        $sql .= " child.id, child.name, child.sticker_id, child.task_parent, child.deadline,";
        $sql .= " child.priority, child.weight,";
        $sql .= " child.department_id, child.user_id, child.progress,";
        $sql .= " child.status";
        $sql .= " FROM tasks child";
        $sql .= $sqlJoinRaw;
        $sql .= " WHERE child.deleted_at is null";

        $sql .= ")";

        $sql .= "SELECT";
        $sql .= " parent.id, parent.name, stickers.name as sticker_name, parent.task_parent, parent.deadline,";
        $sql .= " coalesce(nullif(pj.project_names, '{null}'), null) as project_names,";
        $sql .= " priorities.label as priority_name, parent.weight, parent.department_id,";
        $sql .= " users.fullname as fullname, parent.progress, parent.status,";
        $sql .= " to_char(min(tt.start_time), 'DD-MM-YYYY') as start_time,";
        $sql .= " to_char(max(tt.end_time), 'DD-MM-YYYY') as end_time,";
        $sql .= " coalesce(sum(tt.estimate_time), 0) as total_estimate_time,";
        $sql .= " coalesce(sum(tt.time_spent), 0) as total_time_spent";
        $sql .= " from parent";

        $join = " left join (";
        $searchProject = false;
        if (isset($requestDatas['project_id']) && count($requestDatas['project_id']) > 0) {
            $join = " inner join (";
            $searchProject = true;
        }
        //left join task_projects table
        $sql .= $join;
        $sql .= " select task_projects.task_id, array_agg(projects.name) as project_names";
        $sql .= " from task_projects";
        $sql .= " left join projects on projects.id = task_projects.project_id and projects.deleted_at is null";
        $sql .= " where task_projects.deleted_at is null";
        if ($searchProject) {
            $stringSearchProjectIds = implode(',', array_filter($requestDatas['project_id']));

            $sql .= " and task_projects.task_id in (";
            $sql .= " select task_id from task_projects";
            $sql .= " where deleted_at is null and (";

            if (!empty($stringSearchProjectIds)) {
                $sql .= " project_id in (".$stringSearchProjectIds.")";
            }

            if (in_array(0, $requestDatas['project_id'])) {
                if (!empty($stringSearchProjectIds)) {
                    $sql .= " or task_projects.project_id is null";
                } else {
                    $sql .= " task_projects.project_id is null";
                }
            }

            $sql .= " ) )";
        }
        $sql .= " group by task_id";
        $sql .= " ) pj on pj.task_id = parent.id";

        //left/inner join task_timings table
        $join = " inner join (";
        $searchTime = false;
        if (!empty($requestDatas['start_time']) && !empty($requestDatas['end_time'])) {
            $join = " left join (";
            $searchTime = true;
        }
        $sql .= $join;
        $sql .= " select task_id, min(work_date) as start_time, max(work_date) as end_time,";
        $sql .= " sum(estimate_time) as estimate_time, sum(time_spent) as time_spent";
        $sql .= " from task_timings";
        $sql .= " where deleted_at is null";
        if ($searchTime) {
            $sql .= " and work_date >= '".Carbon::create($requestDatas['start_time'])->format('Y/m/d')."'";

            $sql .= " and work_date <= '".Carbon::create($requestDatas['end_time'])->format('Y/m/d')."'";
        }
        $sql .= " group by task_id";
        $sql .= " ) tt on tt.task_id = parent.id";

        $sql .= " LEFT JOIN users ON users.id = parent.user_id";
        $sql .= " LEFT JOIN priorities ON priorities.id = parent.priority";
        $sql .= " LEFT JOIN stickers ON stickers.id = parent.sticker_id";

        if ($stringSearchIds && !$isOnlySearchIds) {
            $stringSearchIds = implode(',', $requestDatas['ids']);

            $sql .= " where parent.id in (";
            $sql .= " WITH RECURSIVE sub_parent AS (";
            $sql .= " SELECT";
            $sql .= " parent.id";
            $sql .= " FROM parent";
            $sql .= " WHERE parent.id in (".$stringSearchIds.")";
            $sql .= " union";
            $sql .= " SELECT";
            $sql .= " sub_child.id";
            $sql .= " FROM parent sub_child";
            $sql .= " JOIN sub_parent sub_parent on sub_parent.id = sub_child.task_parent )";
            $sql .= " SELECT";
            $sql .= " id";
            $sql .= " FROM sub_parent";
            $sql .= " )";
        }

        $sql .= " group by parent.id, parent.name, pj.project_names, stickers.id, parent.task_parent, parent.deadline,";
        $sql .= " priorities.id, parent.weight, parent.department_id, users.id, parent.progress, parent.status";

        $model = new Task();
        $result = DB::connection($model->getConnectionName())->select($sql);

        return $result;
    }

    private function createHierarchicalTree($tree, $root = 0)
    {
        $return = array();
        foreach ($tree as $child => $parent) {
            if ($parent->task_parent == $root) {
                if (isset($tree[$child]->id) === true) {
                    $parent->grandchildren = $this->createHierarchicalTree($tree, $tree[$child]->id);
                }

                unset($tree[$child]);

                $return[] = $parent;
            }
        }

        return empty($return) ? null : $return;
    }

    /** Append SQL if json is requested
     *
     * @param  $requestDatas
     * @param  $query
     * @return $addedQuery
    */
    private function addSqlWithSorting($requestDatas, $query)
    {
        $addedQuery = $query;

        //Change the SQL according to the requested search conditions
        if (!empty($requestDatas['name'])) {
            $name = mb_strtolower(urldecode($requestDatas['name']), 'UTF-8');

            if (strpos($name, ',') !== false) {
                $nameArray = explode(',', $name);
            } else {
                $nameArray = [$name];
            }
        
            $addedQuery = $addedQuery->where(function ($query) use ($nameArray) {
                foreach ($nameArray as $name) {
                    if ($name) {
                        $query->orWhere(
                            DB::raw('lower(tasks.name)'),
                            'LIKE',
                            '%'.$name.'%'
                        );
                    }
                }
            });
        }

        if (isset($requestDatas['department_id']) && count($requestDatas['department_id']) > 0) {
            $departmentIds = array_filter($requestDatas['department_id']);

            $addedQuery = $addedQuery->where(function ($groupQuery) use ($departmentIds, $requestDatas) {
                if (!empty($departmentIds)) {
                    $groupQuery->whereIn('tasks.department_id', $departmentIds);
                }

                if (in_array(0, $requestDatas['department_id'])) {
                    $groupQuery->orWhereNull('tasks.department_id');
                }
            });
        }

        if (isset($requestDatas['user_id']) && count($requestDatas['user_id']) > 0) {
            $userIds = array_filter($requestDatas['user_id']);

            $addedQuery = $addedQuery->where(function ($groupQuery) use ($userIds, $requestDatas) {
                if (!empty($userIds)) {
                    $groupQuery->whereIn('tasks.user_id', $userIds);
                }

                if (in_array(0, $requestDatas['user_id'])) {
                    $groupQuery->orWhereNull('tasks.user_id');
                }
            });
        }

        if (isset($requestDatas['status']) && count($requestDatas['status']) > 0) {
            $addedQuery = $addedQuery->whereIn('tasks.status', $requestDatas['status']);
        }

        return $addedQuery;
    }

    private function setExcelData($worksheet, $col, $row, $data, $type_string = "s")
    {
        $worksheet->getCell($col . $row)->setValueExplicit($data, $type_string);
        $style = $worksheet->getStyle($col . $row);
        $style->getFont()->setName('BIZ UD????')->getColor()->setRGB('000000');
        $style->getFont()->setSize(10)->setBold(false);
    }
}
