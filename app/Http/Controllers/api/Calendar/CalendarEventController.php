<?php

namespace App\Http\Controllers\api\Calendar;

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
use App\Models\Calendar;
use App\Models\CalendarEvent;
use App\Models\Interviewer;

use App\Http\Requests\api\Calendar\CalendarRegisterRequest;
use App\Http\Requests\api\Calendar\CalendarUpdateRequest;

use Carbon\Carbon;
use Storage;

/**
 * Employee API
 *
 * @group Employee
 */
class CalendarEventController extends Controller
{
    public function getSelectboxes()
    {
        try {
            // $departments = config('const.departments');
            // $departments = array_map(function ($value, $label) {
            //     return ['value' => $value, 'label' => $label];
            // }, array_keys($departments), $departments);
            
            $colors = config('const.calendar_colors');
            $status = config('const.calendar_status');
            // $users = User::select('id', 'fullname')->get();

            $data = [
                // 'users' => $users,
                'colors' => $colors,
                'status' => $status,
                // 'departments' => $departments,
            ];

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR ,
                    'errors' => $e->getMessage(),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEventList(Request $request)
    {
        try {
            $data = CalendarEvent::select('id', 'name', 'class_color')
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'asc')
            ->get();

            $calendarColors = config('const.calendar_colors');

            $data->transform(function ($item) use ($calendarColors) {
                $color = collect($calendarColors)->firstWhere('value', $item->class_color);
                $item->class_color_name = $color['label'] ?? '';
                $item->key_color = $color['key'] ?? '';
                return $item;
            });

            return response()->json($data);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function storeConfig(Request $request)
    {
        $requestDatas = $request->all();

        try { 
            DB::beginTransaction();
            //insert employee
            $calendarEvent = CalendarEvent::create([
                'user_created' => Auth()->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-003'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }

    public function quickUpdateConfig(Request $request)
    {
        try {
            $requestDatas = $request->all();
            $calendarEvent = CalendarEvent::findOrFail($requestDatas['id']);

            DB::beginTransaction();
            foreach($requestDatas as $key => $val){
                $calendarEvent->$key = $val;
            }
            $calendarEvent->save();

            DB::commit();
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

    public function destroyConfig(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $calendarEvent = CalendarEvent::findOrFail($requestDatas['id'])->delete();
            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function storeCalendar(CalendarRegisterRequest $request)
    {
        $requestDatas = $request->all();

        try {
            DB::beginTransaction();
            //insert calendar
            $calendar = Calendar::create([
                'name' => $requestDatas['name'],
                'date' => $requestDatas['date'],
                'event_id' => $requestDatas['event_id'],
                'start_time' => $requestDatas['start_time'],
                'end_time' => $requestDatas['end_time'],
                'department_id' => $requestDatas['department_id'] ?? null,
                'description' => $requestDatas['description'] ?? null,
                'user_created' => Auth()->user()->id,
                'status' => $requestDatas['status']
            ]);
            
            if ($calendar) {
                if(isset($requestDatas['user_id']) && count($requestDatas['user_id']) > 0){
                    foreach ($requestDatas['user_id'] as $val) {
                        Interviewer::create([
                            'calendar_id' => $calendar->id,
                            'user_id' => $val,
                            'type' => 1,
                        ]);
                    }
                }
                if(isset($requestDatas['user_join']) && count($requestDatas['user_join']) > 0){
                    foreach ($requestDatas['user_join'] as $val) {
                        Interviewer::create([
                            'calendar_id' => $calendar->id,
                            'user_id' => $val,
                            'type' => 2,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => __('MSG-S-003'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
        }
    }
    public function updateCalendar(CalendarUpdateRequest $request)
    {
        $requestDatas = $request->all();
        try {
            $calendar = Calendar::findOrFail($requestDatas['id']);

            DB::beginTransaction();
            //update calendar
            $calendar->name = $requestDatas['name'];
            $calendar->date = $requestDatas['date'];
            $calendar->event_id = $requestDatas['event_id'];
            $calendar->start_time = $requestDatas['start_time'];
            $calendar->end_time = $requestDatas['end_time'];
            $calendar->department_id = $requestDatas['department_id'] ?? null;
            $calendar->description = $requestDatas['description'] ?? null;
            $calendar->status = $requestDatas['status'];
            $calendar->save();

            if (array_key_exists('user_id', $requestDatas)) {
                Interviewer::where('calendar_id', $calendar->id)->delete();
                if(isset($requestDatas['user_id']) && count($requestDatas['user_id']) > 0){
                    foreach ($requestDatas['user_id'] as $val) {
                        Interviewer::create([
                            'calendar_id' => $calendar->id,
                            'user_id' => $val,
                            'type' => 1,
                        ]);
                    }
                }
                if(isset($requestDatas['user_join']) && count($requestDatas['user_join']) > 0){
                    foreach ($requestDatas['user_join'] as $val) {
                        Interviewer::create([
                            'calendar_id' => $calendar->id,
                            'user_id' => $val,
                            'type' => 2,
                        ]);
                    }
                }
            }

            DB::commit();

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

    public function destroyCalendar(Request $request)
    {
        try {
            $requestDatas = $request->all();

            $calendar = Calendar::findOrFail($requestDatas['id'])->delete();
            if($calendar){
                Interviewer::where('calendar_id', $requestDatas['id'])->delete();
            }
            return response()->json([
                'success' => __('MSG-S-001'),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'errors' => $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getCalendar(Request $request)
    {
        $requestDatas = $request->all();
        try {
            $superAdmin = config('const.super_admin');
            $super_admin_calendar = false;
            $is_config_calendar = false;
            $calendarView = config('const.employee_id_view_all_calendar');
            $is_view_all_calendar = false;
            if(in_array(Auth()->user()->id, $superAdmin) || Auth()->user()->department_id == 7){
                $super_admin_calendar = true;
                $is_config_calendar = true;
            }
            if(in_array(Auth()->user()->id, $calendarView)){
                $is_view_all_calendar = true;
            }
            $calendar = Calendar::select(
                'calendars.id', 
                'calendars.name', 
                'calendars.start_time', 
                'calendars.end_time', 
                'calendars.date', 
                'calendar_events.class_color', 
                'calendars.event_id',
                'calendars.department_id',
                'calendars.user_created',
                )
            ->leftJoin('calendar_events', 'calendar_events.id', '=', 'calendars.event_id')
            ->leftJoin('interviewers', function ($join) {
                $join->on('interviewers.calendar_id', '=', 'calendars.id')
                     ->whereNull('interviewers.deleted_at');
            })
            ->whereNull('calendars.deleted_at');
            $calendar->where(function ($query) use ($requestDatas, $super_admin_calendar, $is_view_all_calendar) {
                if (!$super_admin_calendar && !$is_view_all_calendar) {
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
                        $subquery->where('interviewers.user_id', Auth()->user()->id)
                                ->orWhere('calendars.user_created', Auth()->user()->id);
                    });
                } 
                else if ($is_view_all_calendar) {
                    $query->where('calendars.status','!=', 3)->orWhere('calendars.user_created', Auth()->user()->id);;
                }
            });

            if (isset($requestDatas['event_id'])){
                $calendar->whereIn('calendars.event_id', $requestDatas['event_id']);
            }
            if (!empty($requestDatas['start_date'])) {
                $calendar->whereDate('calendars.date', '>=', $requestDatas['start_date']);
            }
            if (!empty($requestDatas['end_date'])) {
                $calendar->whereDate('calendars.date', '<=', $requestDatas['end_date']);
            }
            $calendar->groupBy('calendars.id','calendar_events.name','calendar_events.class_color');
            $calendar = $calendar->orderBy('calendars.date', 'asc')->orderBy('calendars.start_time', 'asc')->get();

            $calendar = $this->getDepartmentName($calendar);

            $calendarEdit = config('const.employee_id_edit_calendar');
            $is_edit_calendar = false;
            if(in_array(Auth()->user()->id, $calendarEdit) || $super_admin_calendar){
                $is_edit_calendar = true;
            }

            $start_date = Carbon::parse($requestDatas['start_date'])->format('m-d');
            $end_date = Carbon::parse($requestDatas['end_date'])->format('m-d');
            $start_year = Carbon::parse($requestDatas['end_date'])->startOfYear()->format('m-d');
            $end_year = Carbon::parse($requestDatas['start_date'])->endOfYear()->format('m-d');

            $birthday = User::select('users.id', 'users.fullname as name')
                ->selectRaw("TO_CHAR(users.birthday, 'MM-DD') AS date")
                ->whereNull('users.deleted_at')
                ->where('users.user_status', 1)
                ->where(function ($query) use ($start_date, $end_date, $start_year, $end_year) {
                    if ($end_date < $start_date) {
                        $query->whereRaw("TO_CHAR(users.birthday, 'MM-DD') BETWEEN '$start_date' AND '$end_year'")
                            ->orWhereRaw("TO_CHAR(users.birthday, 'MM-DD') BETWEEN '$start_year' AND '$end_date'");
                    } else {
                        $query->whereRaw("TO_CHAR(users.birthday, 'MM-DD') BETWEEN '$start_date' AND '$end_date'");
                    }
                })
                ->orderBy('date', 'asc')->get();
            $data = [
                'data' => $calendar,
                'birthday' => in_array(0,$requestDatas['event_id']) ? $birthday : [],
                'is_edit_calendar' => $is_edit_calendar,
                'is_config_calendar' => $is_config_calendar,
                'id' => Auth()->user()->id
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

    public function getCalendarDetail(Request $request)
    {
        $requestDatas = $request->all();
        try {
            $superAdmin = config('const.super_admin');
            $super_admin_calendar = false;
            $is_config_calendar = false;
            $calendarView = config('const.employee_id_view_all_calendar');
            $is_view_all_calendar = false;
            if(in_array(Auth()->user()->id, $superAdmin) || Auth()->user()->department_id == 7){
                $super_admin_calendar = true;
                $is_config_calendar = true;
            }
            if(in_array(Auth()->user()->id, $calendarView)){
                $is_view_all_calendar = true;
            }
            $calendar = Calendar::select(
                'calendars.id', 
                'calendars.name', 
                'calendars.start_time', 
                'calendars.end_time', 
                'calendars.date', 
                'calendars.event_id',
                'calendars.start_time',
                'calendars.end_time',
                'calendars.description',
                'calendars.department_id',
                'calendar_events.name as name_event',
                'calendar_events.class_color', 
                'calendars.status', 
                DB::raw("STRING_AGG(CASE WHEN interviewers.type = 1 THEN users.fullname ELSE NULL END, ', ') as fullnames"),
                DB::raw("STRING_AGG(CASE WHEN interviewers.type = 2 THEN users.fullname ELSE NULL END, ', ') as user_join"),
                DB::raw("(SELECT fullname FROM users WHERE users.id = calendars.user_created) as name_created"),
            )
            ->leftJoin('calendar_events', 'calendar_events.id', '=', 'calendars.event_id')
            ->leftJoin('interviewers', function ($join) {
                $join->on('interviewers.calendar_id', '=', 'calendars.id')
                     ->whereNull('interviewers.deleted_at');
            })
            ->leftJoin('users', 'users.id', '=', 'interviewers.user_id')
            ->whereNull('calendars.deleted_at')
            ->whereNull('calendar_events.deleted_at');
            if (isset($requestDatas['date'])) {
                $calendar->whereDate('calendars.date', '=', $requestDatas['date']);
            }
            if (isset($requestDatas['department_id']) && is_array($requestDatas['department_id']) && count($requestDatas['department_id']) > 0) {
                $calendar->whereIn('calendars.department_id', $requestDatas['department_id']);
            }       
            if (isset($requestDatas['name']) && !empty($requestDatas['name'])) {
                $calendar = $calendar->where(
                    DB::raw('lower(calendars.name)'),
                    'LIKE',
                    '%'.mb_strtolower(urldecode($requestDatas['name']), 'UTF-8').'%'
                );
            }
            $calendar->where(function ($query) use ($requestDatas, $super_admin_calendar, $is_view_all_calendar) {
                if (!$super_admin_calendar && !$is_view_all_calendar) {
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
                        $subquery->where('interviewers.user_id', Auth()->user()->id)
                                ->orWhere('calendars.user_created', Auth()->user()->id);
                    });
                } 
                else if ($is_view_all_calendar) {
                    $query->where('calendars.status','!=', 3)->orWhere('calendars.user_created', Auth()->user()->id);;
                }
            });
            
            if (isset($requestDatas['event_id'])) {
                $calendar->whereIn('calendars.event_id', $requestDatas['event_id']);
            }
            $calendar->orderBy('calendars.start_time', 'asc');
            $calendar->groupBy('calendars.id','calendar_events.name','calendar_events.class_color');
            $calendar = $calendar->get();
            $calendar = $this->getDepartmentName($calendar);
            
            $calendarEdit = config('const.employee_id_edit_calendar');
            $is_edit_calendar = false;
            if(in_array(Auth()->user()->id, $calendarEdit) || $super_admin_calendar){
                $is_edit_calendar = true;
            }


            $birthday = User::select('users.id', 'users.fullname as name', 'users.department_id')
                ->selectRaw("TO_CHAR(users.birthday, 'MM-DD') AS date")
                ->whereNull('users.deleted_at')
                ->where('users.user_status', 1)
                ->whereRaw("TO_CHAR(users.birthday, 'MM-DD') = ?", Carbon::parse($requestDatas['date'])->format('m-d'))
                ->get();
            $birthday = $this->getDepartmentName($birthday);

            $data = [
                'data' => $calendar,
                // 'birthday' => $birthday,
                'birthday' => !isset($requestDatas['event_id']) || ($requestDatas['event_id'] && in_array(0,$requestDatas['event_id'])) ? $birthday : [],
                'is_edit_calendar' => $is_edit_calendar,
                'is_config_calendar' => $is_config_calendar,
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
    public function getCalendarById(Request $request)
    {
        $requestDatas = $request->all();
        try {
            $data = Calendar::select(
                'calendars.id', 
                'calendars.name', 
                'calendars.start_time', 
                'calendars.end_time', 
                'calendars.date', 
                'calendars.event_id',
                'calendars.start_time',
                'calendars.end_time',
                'calendars.description',
                'calendars.department_id',
                'calendars.status',
                'calendar_events.name as name_event',
                DB::raw("ARRAY_AGG(interviewers.user_id) FILTER (WHERE interviewers.type = 1) as user_id"),
                DB::raw("ARRAY_AGG(interviewers.user_id) FILTER (WHERE interviewers.type = 2) as user_join"),
            )
            ->leftJoin('calendar_events', 'calendar_events.id', '=', 'calendars.event_id')
            ->leftJoin('interviewers', function ($join) {
                $join->on('interviewers.calendar_id', '=', 'calendars.id')
                     ->whereNull('interviewers.deleted_at');
            })
            ->whereNull('calendars.deleted_at');
            
            if(isset($requestDatas['id'])){
                $data->where('calendars.id', $requestDatas['id']);
            }
            $data = $data->groupBy('calendars.id', 'calendars.name', 'calendars.start_time', 'calendars.end_time', 'calendars.date', 'calendars.event_id', 'calendars.description', 'calendars.department_id', 'calendar_events.name')->get();

            return response()->json($data[0]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND ,
                'errors' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
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
