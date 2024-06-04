<?php

namespace App\Http\Controllers\api\Home;

use App\Http\Controllers\Controller;
use App\Http\Controllers\api\CommonController;
use Illuminate\Http\Request;
use \Symfony\Component\HttpFoundation\Response;
use Exception;
use App\Models\User;
use App\Models\UserWork;
use App\Models\Task;
use App\Models\Violation;
use App\Models\Petition;
use App\Models\TimesheetDetail;
use App\Models\DeadlineModification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Holiday;
use App\Models\Project;

use App\Models\Calendar;
use App\Models\CalendarEvent;
use App\Models\Interviewer;

/**
 * Home API
 *
 * @group Home
 */
class HomeController extends Controller
{
    public function getEmployeeInfo()
    {
        try {
            //employees role
            $pmIdsRole = config('const.employee_id_pm_roles');
            // Select specific columns for the authenticated user
            $user = User::select('id', 'avatar', 'fullname', 'position', 'date_official', 'department_id')->find(Auth()->user()->id);
            $isPm = false;
            if (in_array($user->id, $pmIdsRole) || $user->id === 99) {
                $isPm = true;
            }
            $user->is_pm = $isPm;

            $now = Carbon::now();
            // Calculate the differences
            $diff = $now->diff($user->date_official);
            $totalYears = $diff->y;
            $totalMonths = $diff->m;
            $totalDays = $diff->d;

            // Create arrays for year, month, and day parts
            $yearPart = ($totalYears !== 0) ? [$totalYears . ' năm'] : [];
            $monthPart = ($totalMonths !== 0) ? [$totalMonths . ' tháng'] : [];
            $dayPart = ($totalDays !== 0) ? [$totalDays . ' ngày'] : [];

            // Concatenate the parts using implode()
            $user->total_date_official = implode(' ', array_merge($yearPart, $monthPart, $dayPart));
            $user->date_official_DMY = Carbon::parse($user->date_official)->format('d/m/Y');

            return response()->json($user);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }   
    }

    public function getUserWork(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $work = UserWork::select(DB::raw('
                cast(sum(total_day) as numeric(10, 2)) as total_day,
                cast(sum(total_hour) as numeric(10, 2)) as total_hour,
                cast(sum(total_effort_hour) as numeric(10, 2)) as total_effort_hour,
                sum(warrior_1) as warrior_1,
                sum(warrior_2) as warrior_2,
                sum(warrior_3) as warrior_3
            '))
            ->where('user_id', Auth()->user()->id)
            ->whereBetween('month', [
                Carbon::parse($requestDatas['start_date'])->startOfYear(),
                Carbon::parse($requestDatas['end_date'])->endOfYear(),
            ])
            ->first();

            return response()->json($work);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getTimesheets(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $timesheets = [];
            $requestDatas['view'] = 'home';
            $data = CommonController::getTimesheetReport($requestDatas);
            unset($requestDatas['view']);
            if (!is_array($data) || !isset($data[0])) {
                return response()->json($timesheets);
            }

            $timesheets = collect($data[0]['timesheets'])->map(function ($item, $key) {
                $checkIn = isset($item['petition_type']) && in_array(7, $item['petition_type']) && isset($item['is_out_a_day'])
                    ? $item['petition_check_in']
                    : (isset($item['check_in']) ? substr($item['check_in'], 0, 5) : '-:-');

                $checkOut = isset($item['petition_type']) && in_array(7, $item['petition_type']) && isset($item['is_out_a_day'])
                    ? $item['petition_check_out']
                    : (isset($item['check_out']) ? substr($item['check_out'], 0, 5) : '-:-');
            
                $nonOfficeGoouts = $item['non_office_goouts'] ?? 0;
                $officeGoouts = $item['office_goouts'] ?? 0;
                $goOut = $nonOfficeGoouts + $officeGoouts;

                //employees go late or leave early
                $punctualityIssue = isset($item['late_total']) && $item['late_total'] > 0 || isset($item['early_total']) && $item['early_total'] > 0;
                $formattedData = [
                    'time' => $checkIn . ' - ' . $checkOut,
                    'go_out' => $goOut,
                    'petitions' => $item['petition_type'] ?? [],
                    'punctuality_issue' => $punctualityIssue,
                    'workday' => isset($item['workday']) ? $item['workday'] : 0,
                ];

                // Conditionally add 'long_leave' element if isset $item['long_leave']
                if (isset($item['long_leave'])) {
                    $formattedData['long_leave'] = $item['long_leave'];
                }

                // Conditionally add 'is_going_out' element if isset $item['is_going_out']
                if (isset($item['is_going_out'])) {
                    $formattedData['is_going_out'] = $item['is_going_out'];
                }

                // Conditionally add 'is_out_a_day' element if isset $item['is_out_a_day']
                if (isset($item['is_out_a_day'])) {
                    $formattedData['is_out_a_day'] = $item['is_out_a_day'];
                }

                // Conditionally add 'is_holiday' element if isset $item['is_holiday']
                if (isset($item['is_holiday'])) {
                    $formattedData['is_holiday'] = $item['is_holiday'];
                }

                return $formattedData;
            })->keyBy(function ($item, $key) {
                return $key;
            })->all();

            return response()->json($timesheets);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getWorkDetail(Request $request) 
    {
        try {
            $requestDatas = $request->all();
            $allData = Task::join('task_timings', function ($join) use ($requestDatas) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');
                if (!empty($requestDatas['start_date'])) {
                    $join->whereDate('task_timings.work_date', '>=', $requestDatas['start_date']);
                }
                if (!empty($requestDatas['end_date'])) {
                    $join->whereDate('task_timings.work_date', '<=', $requestDatas['end_date']);
                }
            })
            ->where('tasks.deleted_at', null)
            ->where('tasks.user_id', $requestDatas['user_id']);

            $processedCodes = [];
            $data = $allData->get()->reduce(function ($count, $item) use (&$processedCodes) {
                if ($item->status == 1 && !in_array($item->code, $processedCodes)) {
                    $count['waitingTasks']++;
                    $processedCodes[] = $item->code;
                } 
                if ($item->status == 2 && !in_array($item->code, $processedCodes)) {
                    $count['tasksInProgress']++;
                    $processedCodes[] = $item->code;
                } 
                return $count;
            },  [
                'waitingTasks' => 0, 
                'tasksInProgress' => 0,
            ]);

            $data['overdueTasks'] = Task::joinSub(function ($query) use ($requestDatas) {
                $query->select('task_id', DB::raw('MAX(work_date) as max_work_date'))
                    ->from('task_timings')
                    ->whereNull('deleted_at')
                    ->whereBetween('work_date', [$requestDatas['start_date'], $requestDatas['end_date']])
                    ->groupBy('task_id');
            }, 'timings', function ($join) {
                $join->on('tasks.id', '=', 'timings.task_id');
            })
            ->selectRaw('COUNT(DISTINCT tasks.id) as total')
            ->selectRaw('COALESCE(SUM(CASE WHEN tasks.deadline < timings.max_work_date THEN 1 ELSE 0 END), 0) as overdue')
            ->whereNull('tasks.deleted_at')
            ->whereNotNull('tasks.name')
            ->where('tasks.user_id', $requestDatas['user_id'])
            ->get();

            $workTime = CommonController::getWorkTime($requestDatas);
            if (count($workTime) > 0) {
                // Use array_reduce and array_walk_recursive to calculate the sum
                $totalTime = array_reduce($workTime, function ($carry, $item) {
                    array_walk_recursive($item, function ($value, $key) use (&$carry) {
                        if ($key === 'time') {
                            $carry += $value;
                        }
                    });
                    return $carry;
                }, 0);

                // Round the total to two decimal places
                $data['workTime'] = round($totalTime, 2);
            }

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }
    
    public function getWaitingTasks(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $data = Task::join('task_timings', function ($join) use ($requestDatas) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');

                if (!empty($requestDatas['start_date'])) {
                    $join->whereDate('task_timings.work_date', '>=', $requestDatas['start_date']);
                }
                if (!empty($requestDatas['end_date'])) {
                    $join->whereDate('task_timings.work_date', '<=', $requestDatas['end_date']);
                }
            })
            ->select(DB::raw('
                COALESCE(COUNT(tasks.id), 0) as total
            '))
            ->where('tasks.user_id', $requestDatas['user_id'])
            ->where('tasks.status', 1)
            ->get();

            return response()->json($data[0]->total);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getTasksInProgress(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $data = Task::join('task_timings', function ($join) use ($requestDatas) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');

                if (!empty($requestDatas['start_date'])) {
                    $join->whereDate('task_timings.work_date', '>=', $requestDatas['start_date']);
                }
                if (!empty($requestDatas['end_date'])) {
                    $join->whereDate('task_timings.work_date', '<=', $requestDatas['end_date']);
                }
            })
            ->select(DB::raw('
                COALESCE(COUNT(tasks.id), 0) as total
            '))
            ->where('tasks.user_id', $requestDatas['user_id'])
            ->where('tasks.status', 2)
            ->get();

            return response()->json($data[0]->total);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getOverdueTasks(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $data = Task::joinSub(function ($query) use ($requestDatas) {
                $query->select('task_id', DB::raw('MAX(work_date) as max_work_date'))
                    ->from('task_timings')
                    ->whereNull('deleted_at')
                    ->whereBetween('work_date', [$requestDatas['start_date'], $requestDatas['end_date']])
                    ->groupBy('task_id');
            }, 'timings', function ($join) {
                $join->on('tasks.id', '=', 'timings.task_id');
            })
                ->selectRaw('COUNT(DISTINCT tasks.id) as total')
                ->selectRaw('COALESCE(SUM(CASE WHEN tasks.deadline < timings.max_work_date THEN 1 ELSE 0 END), 0) as overdue')
                ->whereNull('tasks.deleted_at')
                ->where('tasks.user_id', $requestDatas['user_id'])
                ->get();

            return response()->json($data[0]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getWorkTime(Request $request)
    {
        try {
            $requestDatas = $request->all();
            $workTime = 0;

            $data = CommonController::getWorkTime($requestDatas);
            if (count($data) > 0) {
                // Use array_reduce and array_walk_recursive to calculate the sum
                $totalTime = array_reduce($workTime, function ($carry, $item) {
                    array_walk_recursive($item, function ($value, $key) use (&$carry) {
                        if ($key === 'time') {
                            $carry += $value;
                        }
                    });
                    return $carry;
                }, 0);

                // Check if $totalTime is a decimal (floating-point) number
                if (is_float($totalTime)) {
                    $workTime = round($workTime, 2); // Round to 2 decimal places
                }
            }

            return response()->json($workTime);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEffortTime(Request $request)
    {
        try {
            $requestDatas = $request->all();
            //get effort time base on rang time last month
            $lastEffortTime = $this->getLastEffortTime($requestDatas);

            //get effort time base on rang time requestDatas
            $requestDatas['view'] = 'home';
            $data = CommonController::getTimesheetReport($requestDatas);
            unset($requestDatas['view']);
            if (!is_array($data) || !isset($data[0])) {
                return response()->json([]);
            }

            $sumOfEffortTime = collect($data[0]['timesheets'])
            ->map(function ($item) {
                $goEarlyLeaveLateSum = ($item['go_early_total'] ?? 0) + ($item['leave_late_total'] ?? 0);
                $adjustedSum = isset($item['click_time_goouts'])
                    ? $goEarlyLeaveLateSum - $item['click_time_goouts']
                    : $goEarlyLeaveLateSum;
        
                return $adjustedSum;
            })
            ->sum();
            $effortTime = round(($sumOfEffortTime/3600), 0);
            $effortTimeTitle = CommonController::getWarriorTitle($requestDatas, $effortTime);

            $data = [
                'last_effort_time' => $lastEffortTime,
                'request_effort_time' => $effortTime,
                'title' => $effortTimeTitle
            ];

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getViolations(Request $request)
    {
        try {
            $types = config('const.violation_type');
            $requestDatas = $request->all();

            $violations = Violation::select('type', 'description')
            ->where('user_id', Auth()->user()->id)
            ->whereBetween('time', [
                Carbon::parse($requestDatas['start_date'])->startOfYear(),
                Carbon::parse($requestDatas['end_date'])->endOfYear()
            ])
            ->orderBy('created_at', 'asc')
            ->get();

            $violationsWithLabels = $violations->map(function ($violation) use ($types) {
                $violationType = collect($types)->firstWhere('value', $violation->type);
                return [
                    'type' => $violationType ? $violationType['label'] : null,
                    'description' => $violation->description
                ];
            });

            return response()->json($violationsWithLabels);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function processAttendance(Request $request)
    {
        try {
            $requestDatas = $request->all();
            $requestDatas['view'] = 'home';
            $rawData = CommonController::getTimesheetReport($requestDatas);
            unset($requestDatas['view']);
            if (!is_array($rawData) || !isset($rawData[0])) {
                return response()->json([]);
            }
            // Get all day of the month except sunday
            $dateArray = [];
            $end_date = Carbon::parse($requestDatas['end_date']);
            $current_date = Carbon::parse($requestDatas['start_date']);
            // get end time if current time is less than end time
            $end_timestamp = ($end_date->isAfter(Carbon::now()) || $end_date->isSameDay(Carbon::now())) ? Carbon::yesterday() : Carbon::parse($requestDatas['end_date']);
            while ($current_date->lte($end_timestamp)) {
                if (!$current_date->isSunday()) {
                    $dateArray[] = $current_date->format('Ymd');
                }
                $current_date->addDay();
            }
            // get missing timesheet log
            $missingDates = array_diff($dateArray, array_keys($rawData[0]['timesheets']));
            // get data timesheet log today
            $rawDataCurrent = isset($rawData[0]['timesheets'][Carbon::now()->format('Ymd')]) ? $rawData[0]['timesheets'][Carbon::now()->format('Ymd')] : [];
            unset($rawData[0]['timesheets'][Carbon::now()->format('Ymd')]);
            $counts = collect($rawData[0]['timesheets'])->reduce(function ($carry, $item, $key) {
                $carry['go_late'] += isset($item['late_total']) && $item['late_total'] > 0 ? 1 : 0;
                $carry['on_time'] +=
                    (
                        isset($item['go_early_total']) && $item['go_early_total'] >= 0
                        &&
                        isset($item['late_total']) && $item['late_total'] == 0
                    )
                    &&
                    (
                        isset($item['leave_late_total']) && $item['leave_late_total'] >= 0
                        &&
                        isset($item['early_total']) && $item['early_total'] == 0
                    )
                    &&
                    (
                        !isset($item['long_leave'])
                    )
                    ? 1
                    : 0;
                $carry['missed_timesheets_log'] +=
                    (
                        (
                            (!isset($item['check_in']) || $item['check_in'] == "")
                            ||
                            (!isset($item['check_out']) || $item['check_out'] == "")
                        )
                        &&
                        (
                            !isset($item['long_leave']) 
                        )
                        &&
                        (
                            !isset($item['petition_type']) || !in_array(2, $item['petition_type'])
                        )
                        &&
                        (
                            !isset($item['is_holiday'])
                        ) 
                    )
                    ? 1
                    : 0;
                return $carry;
            }, [
                'go_late' => 0,
                'on_time' => 0,
                'missed_timesheets_log' => 0,
            ]);
            // count missing timesheet log
            $counts['missed_timesheets'] = count($missingDates);
            //count missed timesheets log today
            if(Carbon::now()->between($current_date, $end_date)){
                $counts['missed_timesheets_today'] = 
                (
                    !isset($rawDataCurrent['check_in'])
                    &&
                    !isset($rawDataCurrent['long_leave']) 
                    &&
                    (
                        !isset($rawDataCurrent['petition_type']) 
                        || 
                        !in_array(2, $rawDataCurrent['petition_type'])
                    )
                )
                ? 1
                : 0;
            } else {
                $counts['missed_timesheets_today'] = 0;
            }
            $countMissedTimesheet = $counts['missed_timesheets_log'] + $counts['missed_timesheets'] + $counts['missed_timesheets_today'];
            $totalCount = $counts['go_late'] + $counts['on_time'] + $countMissedTimesheet;
            $percentGoLate = $counts['go_late'] > 0 ? ($counts['go_late'] / $totalCount) * 100 : 0;
            $percentOnTime = $counts['on_time'] > 0 ? ($counts['on_time'] / $totalCount) * 100 : 0;
            $percentMissed = $countMissedTimesheet > 0 ? ($countMissedTimesheet / $totalCount) * 100 : 0;
            $data = [
                'count' => [
                    'go_late' => $counts['go_late'],
                    'on_time' => $counts['on_time'],
                    'missed_timesheets_log' => $countMissedTimesheet,
                ],
                'percent' => [
                    'go_late' => round($percentGoLate, 1),
                    'on_time' => round($percentOnTime, 1),
                    'missed_timesheets_log' => round($percentMissed, 1),
                ]
            ];
            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAlerts(Request $request)
    {
        try {
            $petitionTypes = config('const.petition_type');
            $requestDatas = $request->all();

            $petitions = Petition::select(
                'petitions.type',
                'petitions.status',
                'petitions.rejected_reason',
                'user_alerts.created_at'
            )
            ->join('user_alerts', 'user_alerts.resource_id', 'petitions.id')
            ->where('petitions.user_id', Auth()->user()->id)
            ->where('user_alerts.resource_type', 'Petition')
            ->whereBetween('user_alerts.created_at', [
                Carbon::now()->startOfDay(),
                Carbon::now()->endOfDay()
            ])
            ->orderBy('user_alerts.created_at', 'desc')
            ->get();

            $timesheets = TimesheetDetail::join('tracking_devices', function ($join) {
                $join->on('tracking_devices.code', '=', 'timesheet_details.device_id');
            })
            ->select(
                'tracking_devices.name',
                'timesheet_details.time'
            )
            ->where('date', Carbon::now())
            ->where('user_code', Auth()->user()->user_code)
            ->orderBy('time', 'desc')
            ->get();

            $petitions = $petitions->map(function ($petition) use ($petitionTypes) {
                $typeInfo = collect($petitionTypes)->firstWhere('id', $petition->type);
                $petition->label = $typeInfo ? $typeInfo['name'] : 'Unknown';
                $petition->datetime = Carbon::parse($petition->created_at)->format('Y/m/d H:i:s');
                // Create the description property
                $petition->description = "Yêu cầu " . $petition->label . " của bạn đã ";

                // Unset the time property
                unset($petition->created_at);

                return $petition;
            });

            $timesheets = $timesheets->map(function ($timesheet) {
                $timesheet->datetime = Carbon::parse($timesheet->time)->format('Y/m/d H:i:s');
                // Create the description property
                $timesheet->description = "Camera " . $timesheet->name . " đã nhận diện bạn lúc ";
                $timesheet->type = 99;

                // Unset the time property
                unset($timesheet->time);

                return $timesheet;
            });

            $mergedArray = array_merge($petitions->toArray(), $timesheets->toArray());
            $sortedArray = collect($mergedArray)->sortByDesc('datetime');
            $reindexedArray = $sortedArray->values()->all();

            return response()->json($reindexedArray);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getLastEffortTime($requestDatas)
    {
        $modifiedRequestDatas = [
            'user_id' => $requestDatas['user_id'],
            'start_date' => Carbon::parse($requestDatas['start_date'])->subMonth(1)->startOfMonth(),
            'end_date' => Carbon::parse($requestDatas['start_date'])->subMonth(1)->endOfMonth()
        ];

        $requestDatas['view'] = 'home';
        $data = CommonController::getTimesheetReport($modifiedRequestDatas);
        unset($requestDatas['view']);
        if (!is_array($data) || !isset($data[0])) {
            return 0;
        }

        $sumOfEffortTime = collect($data[0]['timesheets'])
            ->sum(function ($item) {
                $goEarlyTotal = isset($item['go_early_total']) ? $item['go_early_total'] : 0;
                $leaveLateTotal = isset($item['leave_late_total']) ? $item['leave_late_total'] : 0;
                return $goEarlyTotal + $leaveLateTotal;
            });

        return round(($sumOfEffortTime / 3600), 0);
    }

    public function getWorkTotal(Request $request) 
    {
        try {
            $requestDatas = $request->all();
            $allData = Task::join('task_timings', function ($join) use ($requestDatas) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');
                if (!empty($requestDatas['start_date'])) {
                    $join->whereDate('task_timings.work_date', '>=', $requestDatas['start_date']);
                }
                if (!empty($requestDatas['end_date'])) {
                    $join->whereDate('task_timings.work_date', '<=', $requestDatas['end_date']);
                }
            })
            ->select('tasks.id', 'tasks.status', 'tasks.deadline', 'tasks.code')
            ->where('tasks.deleted_at', null)
            ->whereNotNull('tasks.name')
            ->where(function ($query) use ($requestDatas) {
                unset($requestDatas['start_date']);
                unset($requestDatas['end_date']);
                unset($requestDatas['project_id']);
                foreach ($requestDatas as $key => $val) {
                    $query->where('tasks.'.$key , $val);
                }
            })
            ->groupBy('tasks.id');
            if(Auth()->user()->position === 1){
                $allData->where('tasks.department_id', Auth()->user()->department_id);
            }
            if(isset($requestDatas['project_id'])){
                $allData->join('task_projects', function ($join) use ($requestDatas) {
                    $join->on('tasks.id', '=', 'task_projects.task_id')->whereNull('task_projects.deleted_at');
                });
                $allData->where('task_projects.project_id', $requestDatas['project_id']);
            }
            $allData = $allData->get();
            $data = $allData->reduce(function ($count, $item) {
                $count['waitingTasks'] += ($item->status == 1);
                $count['tasksInProgress'] += ($item->status == 2);
                $count['tasksInPause'] += ($item->status == 3);
                $count['tasksInComplete'] += ($item->status == 4);
                $count['feedbackWaitingTasks'] += ($item->status == 5);
                $count['tasksInProgressNoneDeadline'] += ($item->status == 2 && is_null($item->deadline));
                $count['fixingBug'] += ($item->status == 9);
                return $count;
            }, [
                'waitingTasks' => 0, 
                'tasksInProgress' => 0,
                'tasksInPause' => 0,
                'tasksInComplete' => 0,
                'feedbackWaitingTasks' => 0,
                'tasksInProgressNoneDeadline' => 0,
                'fixingBug' => 0,
            ]);

            $data['overdueTasks'] = Task::joinSub(function ($query) use ($requestDatas) {
                $query->select('task_id', DB::raw('MAX(work_date) as max_work_date'))
                    ->from('task_timings')
                    ->whereNull('deleted_at')
                    ->whereBetween('work_date', [$requestDatas['start_date'], $requestDatas['end_date']])
                    ->groupBy('task_id');
            }, 'timings', function ($join) {
                $join->on('tasks.id', '=', 'timings.task_id');
            })
            ->selectRaw('COUNT(DISTINCT tasks.id) as total')
            ->selectRaw('COALESCE(SUM(CASE WHEN tasks.deadline < timings.max_work_date THEN 1 ELSE 0 END), 0) as overdue')
            ->whereNull('tasks.deleted_at')
            ->whereNotNull('tasks.name')
            ->where(function ($query) use ($requestDatas) {
                unset($requestDatas['start_date']);
                unset($requestDatas['end_date']);
                unset($requestDatas['project_id']);
                foreach ($requestDatas as $key => $val) {
                    $query->Where('tasks.'.$key , $val);
                }
            });
            if(isset($requestDatas['project_id'])){
                $data['overdueTasks']->join('task_projects', function ($join) use ($requestDatas) {
                    $join->on('tasks.id', '=', 'task_projects.task_id')->whereNull('task_projects.deleted_at');
                });
                $data['overdueTasks']->where('task_projects.project_id', $requestDatas['project_id']);
            }
            if(Auth()->user()->position === 1){
                $data['overdueTasks']->where('tasks.department_id', Auth()->user()->department_id);
            }
            $data['overdueTasks'] = $data['overdueTasks']->get();
            // get list overdueTasks of department
            $data['overdueTaskList'] = Task::joinSub(function ($query) use ($requestDatas) {
                $query->select('task_id', DB::raw('MAX(work_date) as max_work_date'))
                    ->from('task_timings')
                    ->whereNull('deleted_at')
                    ->whereBetween('work_date', [$requestDatas['start_date'], $requestDatas['end_date']])
                    ->groupBy('task_id');
            }, 'timings', function ($join) {
                $join->on('tasks.id', '=', 'timings.task_id');
            })
                ->selectRaw('department_id')
                ->selectRaw('COALESCE(SUM(CASE WHEN tasks.deadline < timings.max_work_date THEN 1 ELSE 0 END), 0) as overdue')
                ->selectRaw('COUNT(DISTINCT tasks.id) as total')
                ->whereNull('tasks.deleted_at')
                ->whereNotNull('tasks.name')
                ->where(function ($query) use ($requestDatas) {
                    unset($requestDatas['start_date']);
                    unset($requestDatas['end_date']);
                    unset($requestDatas['project_id']);
                    foreach ($requestDatas as $key => $val) {
                        $query->Where('tasks.'.$key , $val);
                    }
                })
                ->groupBy('department_id');

            if (isset($requestDatas['project_id'])) {
                $data['overdueTaskList']->join('task_projects', function ($join) use ($requestDatas) {
                    $join->on('tasks.id', '=', 'task_projects.task_id')->whereNull('task_projects.deleted_at');
                });
                $data['overdueTaskList']->where('task_projects.project_id', $requestDatas['project_id']);
            }
            if(Auth()->user()->position === 1){
                $data['overdueTaskList']->where('tasks.department_id', Auth()->user()->department_id);
            }
            $data['overdueTaskList'] = $data['overdueTaskList']
                ->get();


            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }

    public function getSelectBoxWorkTotal() 
    {
        try {
            $data['departments'] = $this->getDepartment();
            $dpIds = array_map(function ($department) {
                return $department['id'];
            }, $data['departments'] );
            $data['users'] = User::select('id', 'fullname', 'department_id')->whereIn('department_id', $dpIds)->get();
            $data['projects'] = Project::select('id', 'name')->orderBy('ordinal_number', 'asc')->get();
            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getTimekeepingNotification(Request $request)
    {
        try {
            $requestDatas = $request->all();
            $filteredDepartments = $this->getDepartment();
            $departmentIds = array_column($filteredDepartments, 'id');
            $timesheets = TimesheetDetail::join('tracking_devices', function ($join) {
                $join->on('tracking_devices.code', '=', 'timesheet_details.device_id');
            })
            ->join('users', function ($join) {
                $join->on('timesheet_details.user_code', '=', 'users.user_code');
            })
            ->select(
                'tracking_devices.name',
                'timesheet_details.time',
                'users.fullname',
                'users.avatar',
            )
            ->whereIn('users.department_id', $departmentIds)
            ->where('date', Carbon::now())
            ->orderBy('time', 'desc');
            if(Auth()->user()->position === 1){
                $timesheets->where('users.department_id', Auth()->user()->department_id);
            }
            $timesheets = $timesheets->get();
            $timesheets = $timesheets->map(function ($timesheet) {
                $timesheet->time = Carbon::parse($timesheet->time)->format('H:i');
                // Create the description property
                $timesheet->description = $timesheet->name . " dùng Camera AI ";
                $timesheet->type = 99;
                return $timesheet;
            });
            $sortedArray = collect($timesheets)->sortByDesc('datetime');
            $reindexedArray = $sortedArray->values()->all();

            return response()->json($reindexedArray);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function getTimekeepingTotal(Request $request)
    {
        $requestDatas = $request->all();
        try {
            // get data deadline
            $deadlines = DeadlineModification::join('users', function ($join) {
                $join->on('deadline_modifications.user_id', '=', 'users.id');
            })
            ->where('deadline_modifications.status', 0)
            ->where('deadline_modifications.deleted_at', null)
            ->whereBetween('deadline_modifications.created_at', [
                Carbon::parse($requestDatas['start_date'])->startOfDay(),
                Carbon::parse($requestDatas['end_date'])->endOfDay()
            ]);

            if (Auth()->user()->position === 1) {
                $deadlines->where('users.department_id', Auth()->user()->department_id);
            } elseif (Auth()->user()->position === 2) {
                if (isset($requestDatas['department_id'])) {
                    $deadlines->where('users.department_id', $requestDatas['department_id']);
                }
                else{
                    $filteredDepartments = $this->getDepartment();
                    $departmentIds = array_column($filteredDepartments, 'id');
                    $deadlines->whereIn('users.department_id', $departmentIds);  
                }
            }
 
            $deadlines = $deadlines->count();
            // get data request petition
            $request_petition = Petition::join('users', function ($join) {
                $join->on('petitions.user_id', '=', 'users.id');
            })
            ->where('petitions.status', null)
            ->where('petitions.deleted_at', null)
            ->whereBetween('petitions.created_at', [
                Carbon::parse($requestDatas['start_date'])->startOfDay(),
                Carbon::parse($requestDatas['end_date'])->endOfDay()
            ]);

            if (Auth()->user()->position === 1) {
                $request_petition->where('users.department_id', Auth()->user()->department_id);
            } elseif (Auth()->user()->position === 2) {
                if (isset($requestDatas['department_id'])) {
                    $request_petition->where('users.department_id', $requestDatas['department_id']);
                }
                else{
                    $filteredDepartments = $this->getDepartment();
                    $departmentIds = array_column($filteredDepartments, 'id');
                    $request_petition->whereIn('users.department_id', $departmentIds);  
                }
            }
            
            $request_petition = $request_petition->count();
            
            $requestDatas['view'] = 'home';
            $allData = CommonController::getTimesheetReport($requestDatas);
            unset($requestDatas['view']);
            if (!is_array($allData) || !isset($allData[0])) {
                return response()->json([]);
            }

            $data = array_reduce($allData, function ($carry, $item) {
                if (!isset($item['timesheets']) || empty($item['timesheets'])) {
                    $carry['check_in_none']++;
                    $carry['name_check_in_none'][] = $item['fullname'];
                } else {
                    foreach ($item['timesheets'] as $timesheet) {
                        if (isset($timesheet['check_in']) && $timesheet['check_in'] != "") {
                            $carry['check_in_success']++;
                        }
                        if (!isset($timesheet['check_in']) && 
                            isset($timesheet['petition_type']) && 
                            !in_array(2, $timesheet['petition_type']) && 
                            isset($timesheet['long_leave']) && 
                            $timesheet['long_leave'] != 2 
                            ) {
                            $carry['check_in_none']++;
                            $carry['name_check_in_none'][] = $item['fullname'];
                        }
                        if (isset($timesheet['late_total']) && 
                            $timesheet['late_total'] != 0
                            ) {
                            $carry['late_total']++;
                            $carry['name_late_total'][] = $item['fullname'];
                        }
                        if (isset($timesheet['petition_type']) && 
                            in_array(2, $timesheet['petition_type'])
                            ) {
                            $carry['petition_sick']++;
                            $carry['name_petition_sick'][] = $item['fullname'];
                        }
                        if (isset($timesheet['long_leave']) && 
                            $timesheet['long_leave'] == 2 &&
                            !in_array($item['fullname'], $carry['name_petition_sick'])
                        )  {
                            $carry['petition_sick'] ++;
                            $carry['name_petition_sick'][] = $item['fullname'];
                        }
                    }
                }
                return $carry;
            }, [
                'check_in_success' => 0,
                'check_in_none' => 0,
                'late_total' => 0,
                'petition_sick' => 0,
                'name_check_in_none' => [],
                'name_late_total' => [],
                'name_petition_sick' => [],
            ]);
            $data['request_petition'] = $request_petition;
            $data['request_deadline'] = $deadlines;
            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getViolationsTotal(Request $request)
    {
        try {
            $types = config('const.violation_type');
            $requestDatas = $request->all();

            $violations = Violation::select(
                'violations.type', 
                'violations.description',
                'users.fullname',
                'violations.user_id',
                'violations.time',
            )
            ->join('users', function ($join) {
                $join->on('violations.user_id', '=', 'users.id');
            })
            ->whereBetween('time', [
                Carbon::parse($requestDatas['start_date'])->startOfYear(),
                Carbon::parse($requestDatas['end_date'])->endOfYear()
            ])
            ->orderBy('violations.time', 'asc');
            if (isset($requestDatas['department_id'])) {
                $violations->where('users.department_id', $requestDatas['department_id']);
            } else {
                $filteredDepartments = $this->getDepartment();
                $departmentIds = array_column($filteredDepartments, 'id');
                $violations->whereIn('users.department_id', $departmentIds);  
            }
            if(Auth()->user()->position === 1){
                $violations->where('users.department_id', Auth()->user()->department_id);
            }
            
            $violations = $violations->get();
            $violationsWithLabelsAndCounts = [];

            foreach ($violations as $violation) {
                $violationType = collect($types)->firstWhere('value', $violation->type);

                $key = $violation->fullname . '_' . $violation->user_id;

                if (!isset($counters[$key])) {
                    $counters[$key] = 1;
                } else {
                    $counters[$key]++;
                }
            
                $violationsWithLabelsAndCounts[] = [
                    'type' => $violationType ? $violationType['label'] : null,
                    'description' => $violation->description,
                    'fullname' => $violation->fullname,
                    'count' => $counters[$key],
                    'time' => $violation->time,
                ];
            }
            usort($violationsWithLabelsAndCounts, function ($a, $b) {
                $timeComparison = strcmp($b['time'], $a['time']);
                if ($timeComparison === 0) {
                    return $b['count'] - $a['count'];
                }
            
                return $timeComparison;
            });
            return response()->json(array_values($violationsWithLabelsAndCounts));
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function processDeadline(Request $request)
    {
        try {
            $requestDatas = $request->all();
            $allData = Task::join('task_timings', function ($join) use ($requestDatas) {
                $join->on('tasks.id', '=', 'task_timings.task_id')->whereNull('task_timings.deleted_at');
                if (!empty($requestDatas['start_date'])) {
                    $join->whereDate('task_timings.work_date', '>=', $requestDatas['start_date']);
                }
                if (!empty($requestDatas['end_date'])) {
                    $join->whereDate('task_timings.work_date', '<=', $requestDatas['end_date']);
                }
            })
            ->select('tasks.id')
            ->where(function ($query) {
                $query->where('tasks.status', 4)
                      ->orWhere('tasks.status', 5);
            })
            ->whereNotNull('tasks.name')
            ->groupBy('tasks.id');
            if(isset($requestDatas['project_id']) && $requestDatas['project_id'] !== null){
                $allData->join('task_projects', function ($join) use ($requestDatas) {
                    $join->on('tasks.id', '=', 'task_projects.task_id')->whereNull('task_projects.deleted_at');
                });
                $allData->where('task_projects.project_id', $requestDatas['project_id']);
            }

            if (Auth()->user()->position === 1) {
                $allData->where('tasks.department_id', Auth()->user()->department_id);
            } elseif (Auth()->user()->position === 2) {
                if (isset($requestDatas['department_id'])) {
                    $allData->where('tasks.department_id', $requestDatas['department_id']);
                }
            }
            $allData = $allData->get()->count();
            $deadline = Task::joinSub(function ($query) use ($requestDatas) {
                $query->select('task_id', DB::raw('MAX(work_date) as max_work_date'))
                    ->from('task_timings')
                    ->whereNull('deleted_at')
                    ->whereBetween('work_date', [$requestDatas['start_date'], $requestDatas['end_date']])
                    ->groupBy('task_id');
            }, 'timings', function ($join) {
                $join->on('tasks.id', '=', 'timings.task_id');
            })
            ->selectRaw('COALESCE(SUM(CASE WHEN tasks.deadline < timings.max_work_date THEN 1 ELSE 0 END), 0) as overdue')
            ->whereNotNull('tasks.name')
            ->whereNull('tasks.deleted_at');
            if(isset($requestDatas['project_id']) && $requestDatas['project_id'] !== null){
                $deadline->join('task_projects', function ($join) use ($requestDatas) {
                    $join->on('tasks.id', '=', 'task_projects.task_id')->whereNull('task_projects.deleted_at');
                });
                $deadline->where('task_projects.project_id', $requestDatas['project_id']);
            }
            if (Auth()->user()->position === 1) {
                $deadline->where('tasks.department_id', Auth()->user()->department_id);
            } elseif (Auth()->user()->position === 2) {
                if (isset($requestDatas['department_id'])) {
                    $deadline->where('tasks.department_id', $requestDatas['department_id']);
                }
            }
            $deadline = $deadline->get();
            $count = ($allData + $deadline[0]->overdue) > 0 ? $allData + $deadline[0]->overdue : 1;
            $percentComplete = ($allData / $count) * 100;
            $percentDeadline = ($deadline[0]->overdue / $count) * 100;
            $data = [
                'count' => [
                    'complete' => $allData,
                    'deadline' => $deadline[0]->overdue
                ],
                'percent' => [
                    'complete' => round($percentComplete, 2),
                    'deadline' => round($percentDeadline, 2),
                ]
                
            ];
            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function processChartBar(Request $request)
    {
        try {
            $requestDatas = $request->all();
            switch ($requestDatas['option']) {
                case '2':
                    $dataItem = $this->getTimesheetsBarChart($requestDatas, 'go_early_total');
                    break;
                case '3':
                    $dataItem = $this->getTimesheetsBarChart($requestDatas, 'leave_late_total');
                    break;
                case '4':
                    $dataItem = $this->getworkBarChart($requestDatas, Violation::class, 'violations', 'COUNT(violations.user_id)', 'time');
                    break;
                case '5':
                    $dataItem = $this->getTimesheetsBarChart($requestDatas, 'rate_late');
                    break;
                default:
                    // $dataItem = $this->getworkBarChart($requestDatas, UserWork::class, 'user_works', 'cast(sum(total_effort_hour) as numeric(10, 2))', 'month');
                    $dataItem = $this->getTimesheetsBarChart($requestDatas, 'total_effort_hour');
                    break;
            }
            return response()->json($dataItem);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getTimesheetsBarChart($requestDatas, $sort = '') 
    {
        $requestDatas['view'] = 'home';
        $data = CommonController::getTimesheetReport($requestDatas);
        unset($requestDatas['view']);
        if (!is_array($data) || !isset($data[0])) {
            return response()->json([]);
        }
        foreach ($data as $val) {
            $goEarlyTotalSum = array_reduce($val['timesheets'], function ($carry, $timesheet) {
                if (isset($timesheet['go_early_total']) && $timesheet['go_early_total'] != "") {
                    $carry['go_early_total'] += $timesheet['go_early_total'];
                    $carry['total_effort_hour'] += $timesheet['go_early_total'];
                }
                if (isset($timesheet['leave_late_total']) && $timesheet['leave_late_total'] != "") {
                    $carry['leave_late_total'] += $timesheet['leave_late_total'];
                    $carry['total_effort_hour'] += $timesheet['leave_late_total'];
                }
                if (isset($timesheet['late_total']) && $timesheet['late_total'] != "" && $timesheet['late_total'] != 0 && (!isset($timesheet['petition_type']) || !in_array(1, $timesheet['petition_type']))) {
                    $carry['late_total'] ++;
                }
                if (isset($timesheet['workday']) && $timesheet['workday'] != "" && (!isset($timesheet['petition_type']) || !in_array(2, $timesheet['petition_type']))) {
                    $carry['workday'] += $timesheet['workday'];
                }
                if (isset($timesheet['non_office_time_goouts']) && $timesheet['non_office_time_goouts'] > 0) {
                    $carry['non_office_time_goouts'] += $timesheet['non_office_time_goouts'];
                }
                return $carry;
            }, [
                'go_early_total' => 0,
                'leave_late_total' => 0,
                'late_total' => 0,
                'workday' => 0,
                'total_effort_hour' => 0,
                'non_office_time_goouts' => 0,
            ]);
            
            $dataItem[] = [
                'fullname' => $val['fullname'],
                'id' => $val['id'],
                'go_early_total' => $goEarlyTotalSum['go_early_total']/60/60,
                'leave_late_total' => $goEarlyTotalSum['leave_late_total']/60/60,
                'late_total' => $goEarlyTotalSum['late_total'],
                'workday' => $goEarlyTotalSum['workday'],
                'rate_late' => ($goEarlyTotalSum['workday'] != 0) ? (($goEarlyTotalSum['late_total']) / $goEarlyTotalSum['workday']) * 100 : 0,
                'total_effort_hour' => ($goEarlyTotalSum['total_effort_hour'] - $goEarlyTotalSum['non_office_time_goouts'])/60/60,
            ];
        }
        usort($dataItem, function ($a, $b) use ($sort) {
            return $b[$sort] <=> $a[$sort];
        });
        $data5Item = array_slice($dataItem, 0, 5);
        $allData['fullname'] = [];
        $allData['dataChart'] = [];
        foreach ($data5Item as $val) {
            $allData['fullname'][] = $val['fullname'];
            $allData['dataChart'][] = $val[$sort];
            $allData['late'][] = $val['late_total'];
        }
        return $allData;
    }

    public function getworkBarChart($requestDatas, $modelClass, $tableName, $value, $date) 
    {
        $model = new $modelClass;
        
        $departments = config('const.departments');
        // setup departments
        $departments = array_map(function ($id, $name) {
            return ['id' => $id, 'name' => $name];
        }, array_keys($departments), $departments);
        // filter departments 
        $filteredDepartments = array_values(array_filter($departments, function ($department) {
            $userDepartmentId = Auth()->user()->department_id;
            return ($userDepartmentId == 12) ? ($department['id'] == 12) : ($department['id'] != 12);
        }));

        // $filteredDepartments = $this->getDepartment();
        $departmentIds = array_column($filteredDepartments, 'id');

        $data =  $model->select(
            'user_id',
            'users.fullname',
            DB::raw($value.' as count')
            )
        ->Join('users', function ($join) use ($tableName){
            $join->on($tableName.'.user_id', '=', 'users.id');
        })
        ->whereBetween($date, [
            Carbon::parse($requestDatas['start_date'])->startOfMonth(),
            Carbon::parse($requestDatas['end_date'])->endOfMonth()
        ])
        ->whereIn('users.department_id', $departmentIds)
        ->groupBy($tableName.'.user_id', 'users.fullname')
        ->orderBy('count', 'desc')
        ->limit(5)
        ->get();
        $dataTotal['fullname'] = [];
        $dataTotal['dataChart'] = [];
        if(count($data) != 0){
            foreach ($data as $val) {
                $dataTotal['fullname'][] = $val->fullname;
                $dataTotal['dataChart'][] = $val->count;
            }
        }
        return $dataTotal;
    }

    public function getAlertsManager(Request $request)
    {
        try {
            $petitionTypes = config('const.petition_type');
            $requestDatas = $request->all();
            $filteredDepartments = $this->getDepartment();
            $departmentIds = array_column($filteredDepartments, 'id');
            $petitions = Petition::select(
                'petitions.type',
                'petitions.status',
                'petitions.rejected_reason',
                'petitions.created_at',
                'users.fullname',
                'users.avatar',
            )
            ->join('users', 'users.id', 'petitions.user_id')
            ->where('petitions.status', null)
            ->whereBetween('petitions.created_at', [
                Carbon::now()->startOfDay(),
                Carbon::now()->endOfDay()
            ])
            ->orderBy('petitions.created_at', 'desc');
            if(Auth()->user()->position === 1){
                $petitions->where('users.department_id', Auth()->user()->department_id);
            } elseif (Auth()->user()->position === 2){
                $petitions->whereIn('users.department_id', $departmentIds);
            }
            $petitions= $petitions->get();
            $petitions = $petitions->map(function ($petition) use ($petitionTypes) {
                $typeInfo = collect($petitionTypes)->firstWhere('id', $petition->type);
                $petition->label = $typeInfo ? $typeInfo['name'] : 'Unknown';
                $petition->datetime = Carbon::parse($petition->created_at)->format('Y/m/d H:i:s');
                // Create the description property
                $petition->description = $petition->fullname." - Yêu cầu mới cần duyệt <br> <strong>Loại yêu cầu: ".$petition->label."</strong> ";

                // Unset the time property
                unset($petition->created_at);

                return $petition;
            });

            $deadlines = DeadlineModification::select(
                'deadline_modifications.status',
                'deadline_modifications.created_at',
                'users.fullname',
                'users.avatar',
            )
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'deadline_modifications.user_id');
            })
            ->where('deadline_modifications.status', 0)
            ->where('deadline_modifications.deleted_at', null)
            ->whereBetween('deadline_modifications.created_at', [
                Carbon::now()->startOfDay(),
                Carbon::now()->endOfDay()
            ])
            ->orderBy('deadline_modifications.created_at', 'desc')
            ->get();
            $deadlines = $deadlines->map(function ($deadline){
                // $deadline->datetime = Carbon::parse($deadline->created_at)->format('Y/m/d H:i:s');
                $standardFormatDateTime = Carbon::createFromFormat('d/m/Y H:i:s', $deadline->created_at)->format('Y-m-d H:i:s');
                $deadline->datetime = Carbon::parse($standardFormatDateTime)->format('Y/m/d H:i:s');
                // Create the description property
                $deadline->description = $deadline->fullname." - Yêu cầu deadline mới cần duyệt";

                // Unset the time property
                unset($deadline->created_at);

                return $deadline;
            });

            $mergedArray = array_merge($petitions->toArray(), $deadlines->toArray());
            $sortedArray = collect($mergedArray)->sortByDesc('datetime');
            $reindexedArray = $sortedArray->values()->all();

            return response()->json($reindexedArray);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function switchCompany(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $user = User::findOrFail(Auth()->user()->id);
            // $user->company_id = $requestDatas['company_id'];
            $user->department_id = $requestDatas['department_id'];

            $user->save();

            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
        }
    }

    public function getDepartment()
    {
        $departments = config('const.departments');
        // setup departments
        $departments = array_map(function ($id, $name) {
            return ['id' => $id, 'name' => $name];
        }, array_keys($departments), $departments);
        // filter departments 
        $filteredDepartments = array_values(array_filter($departments, function ($department) {
            $userDepartmentId = Auth()->user()->department_id;
            return ($userDepartmentId == 12) ? ($department['id'] == 12) : ($department['id'] != 12);
        }));

        return $filteredDepartments;
    }

    public function getEventCalendar(Request $request)
    {
        {
            try {
                $calendar = Calendar::select(
                    'calendars.id', 
                    'calendars.name', 
                    'calendars.start_time', 
                    'calendars.end_time', 
                    'calendars.date', 
                    'calendars.start_time',
                    'calendars.end_time',
                    'calendars.description',
                    'calendars.department_id',
                    'calendar_events.name as name_event',
                    'calendars.status', 
                    DB::raw("STRING_AGG(CASE WHEN interviewers.type = 1 THEN users.fullname ELSE NULL END, ', ') as fullnames"),
                )
                ->leftJoin('calendar_events', 'calendar_events.id', '=', 'calendars.event_id')
                ->leftJoin('interviewers', function ($join) {
                    $join->on('interviewers.calendar_id', '=', 'calendars.id')
                         ->whereNull('interviewers.deleted_at');
                })
                ->leftJoin('users', 'users.id', '=', 'interviewers.user_id')
                ->where(function ($query) {
                    $query->where(function ($subquery) {
                        $subquery->where('calendars.status', 0);
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('calendars.status', 1)
                        ->where(function ($subqueryDepartment) {
                            $subqueryDepartment->where('calendars.department_id', Auth()->user()->department_id)
                            ->orWhere('calendars.department_id', null);
                        });
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('calendars.status', 3)
                        ->where('calendars.user_created', Auth()->user()->id);
                    })
                    ->orWhere(function ($subquery) {
                        if (in_array(Auth()->user()->position, [1, 2, 3])) {
                            $subquery->where('calendars.status', 2);
                        }
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('interviewers.user_id', Auth()->user()->id);
                                
                    });
                })
                ->whereNull('calendars.deleted_at')
                ->whereNull('calendar_events.deleted_at')
                ->where('calendars.date', Carbon::now()->format('Y-m-d'));
                
                $calendar->orderBy('calendars.start_time', 'asc');
                $calendar->groupBy('calendars.id','calendar_events.name','calendar_events.class_color');
                $calendar = $calendar->get();
                $calendar = $this->getDepartmentName($calendar);

                return response()->json($calendar);
            } catch (Exception $e) {
                Log::error($e);
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_NOT_FOUND);
            }
        }
    }
    public function getEventCalendarManager(Request $request)
    {
        {
            try {
                $calendar = Calendar::select(
                    'calendars.id', 
                    'calendars.name', 
                    'calendars.start_time', 
                    'calendars.end_time', 
                    'calendars.date', 
                    'calendars.start_time',
                    'calendars.end_time',
                    'calendars.description',
                    'calendars.department_id',
                    'calendar_events.name as name_event',
                    'calendars.status', 
                    DB::raw("STRING_AGG(CASE WHEN interviewers.type = 1 THEN users.fullname ELSE NULL END, ', ') as fullnames"),
                )
                ->leftJoin('calendar_events', 'calendar_events.id', '=', 'calendars.event_id')
                ->leftJoin('interviewers', function ($join) {
                    $join->on('interviewers.calendar_id', '=', 'calendars.id')
                         ->whereNull('interviewers.deleted_at');
                })
                ->leftJoin('users', 'users.id', '=', 'interviewers.user_id')
                ->where(function ($query) {
                    $query->where(function ($subquery) {
                        $subquery->where('calendars.status', 0);
                    })
                    ->orWhere(function ($subquery) {
                        $subquery->where('calendars.status', 1)
                        ->where(function ($subqueryDepartment) {
                            $subqueryDepartment->where('calendars.department_id', Auth()->user()->department_id)
                            ->orWhere('calendars.department_id', null);
                        });
                    })
                    ->orWhere(function ($subquery) {
                        if (in_array(Auth()->user()->position, [1, 2, 3])) {
                            $subquery->where('calendars.status', 2);
                        }
                    });
                })
                ->whereNull('calendars.deleted_at')
                ->whereNull('calendar_events.deleted_at')
                ->where('calendars.date', Carbon::now()->format('Y-m-d'));
                
                $calendar->orderBy('calendars.start_time', 'asc');
                $calendar->groupBy('calendars.id','calendar_events.name','calendar_events.class_color');
                $calendar = $calendar->get();
                $calendar = $this->getDepartmentName($calendar);

                $birthday = User::select('users.id', 'users.fullname as name', 'users.department_id')
                    ->selectRaw("TO_CHAR(users.birthday, 'MM-DD') AS date")
                    ->whereNull('users.deleted_at')
                    ->where('users.user_status', 1)
                    ->whereRaw("TO_CHAR(users.birthday, 'MM-DD') = ?", Carbon::now()->format('m-d'))
                    ->get();
                $birthday = $this->getDepartmentName($birthday);
                $data = [
                    'data' => $calendar,
                    'birthday' => $birthday,
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
    }

    public function getDepartmentName($data){
        $departments = config('const.departments');
        $calendar_status = config('const.calendar_status');
        $data = $data->map(function ($item) use ($departments, $calendar_status) {
            if ($item->department_id != null) {
                $item->department_id = $departments[$item->department_id];
            }
            foreach ($calendar_status as $val) {
                if (isset($item->status) && $val['value'] == $item->status) {
                    $item->status = $val['label'];
                    $item->status_id = $val['value'];
                }
            }

            return $item;
        });
        return $data;
    }
}