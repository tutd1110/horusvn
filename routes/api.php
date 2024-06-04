<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\WhoAmIController;
use App\Http\Controllers\api\CommonController;
use App\Http\Controllers\api\Home\HomeController;
use App\Http\Controllers\api\Employee\EmployeeController;
use App\Http\Controllers\api\Employee\EmployeeReviewController;
use App\Http\Controllers\api\Employee\EmployeeProfileController;
use App\Http\Controllers\api\Employee\ViolationController;
use App\Http\Controllers\api\Review\ReviewController;
use App\Http\Controllers\api\Report\ReportController;
use App\Http\Controllers\api\Project\ProjectController;
use App\Http\Controllers\api\Task\TaskController;
use App\Http\Controllers\api\Task\TaskDeadlineController;
use App\Http\Controllers\api\Task\DeadlineModificationController;
use App\Http\Controllers\api\Task\TaskFileController;
use App\Http\Controllers\api\Task\TaskProjectController;
use App\Http\Controllers\api\Task\TaskAssignmentController;
use App\Http\Controllers\api\Task\TaskTimingController;
use App\Http\Controllers\api\Task\DepartmentTaskController;
use App\Http\Controllers\api\Task\MyTaskController;
use App\Http\Controllers\api\WeightedFluctuation\WeightedFluctuationController;
use App\Http\Controllers\api\Petition\PetitionController;
use App\Http\Controllers\api\Timesheet\TimesheetController;
use App\Http\Controllers\api\Timesheet\TimesheetDetailController;
use App\Http\Controllers\api\Holiday\HolidayController;
use App\Http\Controllers\api\Holiday\HolidayOffsetController;
use App\Http\Controllers\api\TrackingDevice\TrackingDeviceController;
use App\Http\Controllers\api\PartnerConfig\PartnerConfigController;
use App\Http\Controllers\api\Priority\PriorityController;
use App\Http\Controllers\api\Sticker\StickerController;
use App\Http\Controllers\api\LogRoute\LogRouteController;
use App\Http\Controllers\api\Post\PostController;
use App\Http\Controllers\api\Journal\JournalController;
use App\Http\Controllers\api\Forum\ForumController;
use App\Http\Controllers\api\Purchase\PurchaseController;
use App\Http\Controllers\api\Working\WorkingTimeController;

use App\Http\Controllers\api\Comment\TaskAssignmentCommentController;

use App\Http\Controllers\api\Export\TaskExportController;
use App\Http\Controllers\api\Export\ReportExportController;
use App\Http\Controllers\api\Export\StickerExportController;
use App\Http\Controllers\api\Export\TaskAssignmentExportController;
use App\Http\Controllers\api\Export\WZAndroid0022ExportController;
use App\Http\Controllers\api\Import\TaskImportController;
use App\Http\Controllers\api\Import\StickerImportController;
use App\Http\Controllers\api\Import\WZAndroid0022ImportController;
use App\Http\Controllers\api\Export\TimesheetReportExportController;

use App\Http\Controllers\api\WZAndroid0022\WZAndroid0022Controller;

use App\Http\Controllers\api\Calendar\CalendarEventController;
use App\Http\Controllers\api\Export\StatistialTopExportController;
use App\Http\Controllers\api\Tracking\TrackingGameController;
use App\Http\Controllers\api\OrderStore\OrderController;
use App\Http\Controllers\api\OrderStore\OrderSettingController;
use App\Http\Controllers\api\OrderStore\OrderStatistialController;
use App\Http\Controllers\api\OrderStore\OrderStoreController;
use App\Http\Controllers\api\OrderStore\OrderStoreMenuController;
use App\Http\Controllers\api\Statistial\StatistialController;
use App\Http\Controllers\api\Export\EmployeeListExportController;

use App\Http\Controllers\api\Management\CompanyController;
use App\Http\Controllers\api\Management\DepartmentController;

use App\Http\Controllers\api\Warrior\WarriorProjectController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['auth:sanctum', 'log.route']], function () {
    Route::get('/whoami', WhoAmIController::class);

    Route::group(['prefix' => 'log', 'controller' => LogRouteController::class], function () {
        Route::get('/get_log_review', 'getLogReview');
        Route::get('/get_log_routes', 'getLogRoutes');
        Route::get('/get_selboxes', 'getSelboxes');
    });

    Route::group(['prefix' => 'home', 'controller' => HomeController::class], function () {
        Route::get('/get-info', 'getEmployeeInfo');
        Route::get('/get-work', 'getUserWork');
        Route::post('/get-timesheets', 'getTimesheets');
        Route::post('/get-waiting-tasks', 'getWaitingTasks');
        Route::post('/get-tasks-in-progress', 'getTasksInProgress');
        Route::post('/get-overdue-tasks', 'getOverdueTasks');
        Route::post('/get-work-time', 'getWorkTime');
        Route::post('/get-work-detail', 'getWorkDetail');//all
        Route::post('/get-effort-time', 'getEffortTime');
        Route::post('/get-violations', 'getViolations');
        Route::post('/get-process-attendance', 'processAttendance');
        Route::post('/get-alerts', 'getAlerts');
        Route::get('/get-selectbox-work-total', 'getSelectBoxWorkTotal');
        Route::post('/switch-company', 'switchCompany');

        Route::post('/get-work-total', 'getWorkTotal');
        Route::get('/get-notification-timekeeping', 'getTimekeepingNotification');
        Route::post('/get-time-keeping-total', 'getTimekeepingTotal');
        Route::post('/get-violation-total', 'getViolationsTotal');
        Route::post('/get-process-deadline', 'processDeadline');
        Route::post('/get-chart-bar', 'processChartBar');
        Route::post('/get-alerts-manager', 'getAlertsManager');

        Route::post('/get-event-calendar', 'getEventCalendar');
        Route::post('/get-event-calendar-manager', 'getEventCalendarManager');
    });

    Route::group(['prefix' => 'common', 'controller' => CommonController::class], function () {
        Route::get('/stickers', 'getStickers');
        Route::get('/departments', 'getDepartments');
        Route::get('/departments-job', 'getDepartmentsJob');
        Route::get('/review_selboxes', 'getReviewSelboxes');
        Route::get('/get_employees', 'getEmployees');
        Route::post('/reload_from_task_timing', 'reloadFromTaskTiming');
        Route::post('/reload_from_task_project', 'reloadFromTaskProject');
        Route::get('/user_type', 'getUserType');
        Route::get('/get_warrior_name', 'getWarriorName');
        Route::get('/get_employees_working', 'getEmployeesWorking');
        Route::get('/get_user_login', 'getUserLogin');
        Route::get('/get_user_gender', 'getUserGender');
    });

    Route::group(['prefix' => 'employee', 'controller' => EmployeeController::class], function () {
        Route::post('/get_employee_list', 'getEmployeeList');
        Route::get('/get_selectBoxes', 'getSelectBoxes');
        Route::get('/get_employee_by_id', 'getEmployeeById');
        Route::post('/store', 'store');
        Route::patch('/update', 'update');
        Route::delete('/delete', 'destroy');
        Route::post('/check_out', 'checkOut');
        Route::post('/go_out', 'goOut');
        Route::post('/get_in', 'getIn');
        Route::get('/get_log_employee_outs', 'getLogEmployeeOuts');
        Route::post('/check_in_by_hand', 'checkInByHand');
        Route::get('/get-notifications', 'getNotifications');
        Route::post('/read-noti', 'readNotification');
        Route::post('/read-all-notis', 'readAllNotifications');
        Route::post('/buzz', 'onBuzz');
    });

    Route::group(['prefix' => 'employee/review', 'controller' => EmployeeReviewController::class], function () {
        Route::get('/get_employee_list_with_review', 'getEmployeeListWithReview');
        Route::get('/get_employees', 'getEmployees');
        Route::get('/get_reviews', 'getReviews');
        Route::get('/get_reviews_by_employee_id', 'getReviewsByEmployeeId');
        Route::get('/get_note', 'getNote');
        Route::post('/file/store', 'storeReviewFile');
        Route::post('/comment', 'comment');
        Route::post('/submit', 'submit');
        Route::patch('/undo', 'undoReview');
        Route::patch('/update', 'update');
        Route::patch('/employee_answers', 'employeeAnswers');
        Route::patch('/update-note', 'updateNote');
        Route::post('/add-mentor', 'addMentor');
        Route::post('/add-leader', 'addLeader');
        Route::post('/add-pm', 'addPM');
        Route::delete('/delete', 'destroy');
        Route::delete('/file/delete', 'deleteReviewFile');
    });

    Route::group(['prefix' => 'employee/profile', 'controller' => EmployeeProfileController::class], function () {
        Route::get('/get_main_info', 'getMainInfo');
        Route::get('/get_projects', 'getProjects');
        Route::get('/get_personal_info', 'getPersonalInfo');
        Route::get('/get_alt_info', 'getAltInfo');
        Route::get('/get_job_details', 'getJobDetails');
        Route::patch('/personal_info/update', 'updatePersonalInfo');
        Route::patch('/alt_info/update', 'updateAltInfo');
        Route::patch('/job_detail/update', 'updateJobDetail');
        //Employee Mentor Manager
        Route::get('/get_mentees', 'getMentees');
        Route::post('/mentee/store', 'storeMentee');
        Route::delete('/mentee/delete', 'menteeDestroy');
        //Employee profile awards
        Route::get('/get_awards', 'getAwards');
        Route::post('/award/store', 'storeAward');
        Route::delete('/award/delete', 'awardDestroy');
        //Employee profile activities
        Route::get('/get_activities', 'getActivities');
        Route::post('/activity/store', 'storeActivity');
        Route::delete('/activity/delete', 'activityDestroy');
        //Employee profile Equipment Handover
        Route::get('/get_equipment_handovers', 'getEquipmentHandovers');
        Route::post('/equipment_handover/store', 'storeEquipmentHandover');
        Route::delete('/equipment_handover/delete', 'equipmentHandoverDestroy');
        //Employee childrens
        Route::post('/employee_children/store', 'storeChildren');
        Route::delete('/employee_children/delete', 'destroyChildren');
    });

    Route::group(['prefix' => 'violation', 'controller' => ViolationController::class], function () {
        Route::get('/get_types', 'getType');
        Route::get('/get_violations_by_employee_id', 'getViolationsByEmployeeId');
        Route::get('/get_violations_by_id', 'getViolationsById');
        Route::post('/store', 'store');
        Route::post('/uploadImage', 'uploadImage');
        Route::patch('/update', 'update');
        Route::delete('/delete', 'destroy');
    });

    Route::group(['prefix' => 'review', 'controller' => ReviewController::class], function () {
        Route::get('/load_review_data', 'loadReviewData');
        Route::post('/send_review', 'sendReview');
        Route::post('/store', 'store');
        Route::patch('/update', 'update');
    });

    Route::group(['prefix' => 'report', 'controller' => ReportController::class], function () {
        Route::get('/get_report', 'getReport');
        Route::get('/get_user_report', 'getUserReport');
        Route::get('/get_select_boxes', 'getSelectBoxes');
        Route::get('/get_workday_selboxes', 'getWorkdayReportSelboxes');
        Route::post('/get_workday_reports', 'getWorkdayReports');
    });

    Route::group(['prefix' => 'project', 'controller' => ProjectController::class], function () {
        Route::get('/get_project', 'getProject');
        Route::get('/get_select_boxes', 'getSelectBoxes');
        Route::get('/get_users', 'getUsers');
        Route::get('/get_project_by_id', 'getProjectById');
        Route::post('/store', 'store');
        Route::patch('/update', 'update');
        Route::delete('/delete', 'destroy');
        Route::patch('/quick_update', 'quickUpdate');
    });

    Route::group(['prefix' => 'task', 'controller' => TaskController::class], function () {
        Route::get('/get_selectboxes', 'getSelectboxes');
        Route::get('/get_selectboxes_for_create_update', 'getSelectboxesForCreateUpdate');
        Route::post('/get_select_boxes_by_department_id', 'getSelectBoxesByDepartmentId');
        Route::post('/get_task_list', 'getTaskList');
        Route::post('/get_tasks_with_tree_data', 'getTasksWithTreeData');
        Route::get('/get_task_by_id', 'getTaskById');
        Route::post('/store', 'store');
        Route::patch('/update', 'update');
        Route::patch('/quick_update', 'quickUpdate');
        Route::patch('/update_multiple', 'updateMultiple');
        Route::delete('/delete', 'destroy');
        Route::post('/delete_multiple', 'deleteMultiple');

        Route::get('/sync_data', 'SyncData');
        Route::get('/insert_task_id_to_task_timing', 'insertTaskIdToTaskTiming');

        Route::post('/get_task_list_gantt', 'getTaskListGantt');
        Route::get('/get_selectboxes_gantt', 'getSelectboxesGantt');
        Route::post('/store_gantt', 'storeGantt');
        Route::patch('/update_gantt', 'updateGantt');
        Route::post('/delete_gantt', 'destroyGantt');
        Route::post('/change_parent_gantt', 'changeParentGantt');

        Route::post('/get_sticker', 'getStickers');

    });

    Route::group(['prefix' => 'task-deadline', 'controller' => TaskDeadlineController::class], function () {
        Route::get('/employee-info', 'getEmployeeInfo');
        Route::get('/list', 'getList');
        Route::post('/store', 'store');
        Route::delete('/delete', 'destroy');
        Route::patch('/quick_update', 'quickUpdate');
    });

    Route::group(['prefix' => 'deadline-modification', 'controller' => DeadlineModificationController::class], function () {
        Route::get('/get_selboxes', 'getSelboxes');
        Route::post('/list', 'getList');
        Route::post('/store', 'store');
        Route::post('/updated-status', 'updatedStatus');
        Route::delete('/delete', 'destroy');
    });

    Route::group(['prefix' => 'task/file', 'controller' => TaskFileController::class], function () {
        Route::post('/store', 'store');
        Route::delete('/delete', 'destroy');
    });

    Route::group(['prefix' => 'task_projects', 'controller' => TaskProjectController::class], function () {
        Route::get('/get_selbox', 'getSelbox');
        Route::get('/list', 'list');
        Route::post('/store', 'store');
        Route::patch('/quick_update', 'quickUpdate');
        Route::delete('/delete', 'destroy');
    });

    Route::group(['prefix' => 'task_assignments', 'controller' => TaskAssignmentController::class], function () {
        Route::get('/get_task_assignment_by_id', 'getTaskAssignmentById');
        Route::post('/get_tasks', 'getTasks');
        Route::post('/clone-by-id', 'cloneById');
        Route::post('/store', 'store');
        Route::patch('/update', 'update');
        Route::delete('/delete', 'destroy');
        Route::get('/dsc-selbox', 'getDSelfCreatedSelBox');
        Route::post('/dsc-issues', 'getDSelfCreatedIssues');
        Route::post('/dsc-total', 'getDSelfCreatedTotal');
        Route::post('/get_dsc_report', 'getDSelfCreatedReport');
        Route::get('/da-selbox', 'getDAssignedSelBox');
        Route::post('/da-issues', 'getDAssignedIssues');
        Route::post('/da-total', 'getDAssignedTotal');
        Route::post('/get_da_report', 'getDAssignedReport');
        Route::get('/psc-selbox', 'getPSelfCreatedSelBox');
        Route::post('/psc-issues', 'getPSelfCreatedIssues');
        Route::post('/psc-total', 'getPSelfCreatedTotal');
        Route::get('/pa-selbox', 'getPAssignedSelBox');
        Route::post('/pa-issues', 'getPAssignedIssues');
        Route::post('/pa-total', 'getPAssignedTotal');
    });

    Route::group([
        'prefix' => 'task_assignment_comments',
        'controller' => TaskAssignmentCommentController::class
    ], function () {
        Route::get('/get_user_login', 'getUserLogin');
        Route::get('/list', 'getListComment');
        Route::post('/store', 'store');
        Route::delete('/delete', 'destroy');
    });

    Route::group(['prefix' => 'task_timings', 'controller' => TaskTimingController::class], function () {
        Route::get('/get_selboxes', 'getSelboxes');
        Route::get('/list', 'getList');
        Route::get('/issues', 'getIssues');
        Route::post('/store', 'store');
        Route::patch('/update', 'update');
        Route::delete('/delete', 'destroy');
        Route::post('/delete_multiple', 'deleteMultiple');
    });

    Route::group(['prefix' => 'department/task', 'controller' => DepartmentTaskController::class], function () {
        Route::get('/get_selectboxes', 'getSelectboxes');
        Route::get('/get_selectboxes_for_create_update', 'getSelectboxesForCreateUpdate');
        Route::get('/get_task_list', 'getTaskList');
        Route::get('/get_task_info', 'getTaskInfo');
        Route::get('/get_task_info_by_employee', 'getTaskInfoByEmployee');
        Route::get('/get_select_boxes_by_department_id', 'getSelectBoxesByDepartmentId');
        Route::get('/get_task_description_by_id', 'getTaskDescriptionById');
        Route::post('/get_tasks_with_tree_data', 'getTasksWithTreeData');
        Route::get('/get_task_by_id', 'getTaskById');
        Route::post('/store', 'store');
        Route::patch('/update', 'update');
        Route::patch('/quick_update', 'quickUpdate');
        Route::delete('/delete', 'destroy');
    });

    Route::group(['prefix' => 'me/task', 'controller' => MyTaskController::class], function () {
        Route::get('/get_selectboxes', 'getSelectboxes');
        Route::get('/get_selectboxes_for_create_update', 'getSelectboxesForCreateUpdate');
        Route::get('/get_task_list', 'getTaskList');
        Route::get('/get_task_info', 'getTaskInfo');
        Route::get('/get_tasks_with_tree_data', 'getTasksWithTreeData');
        Route::get('/get_task_by_id', 'getTaskById');
        Route::post('/store', 'store');
        Route::patch('/update', 'update');
        Route::patch('/quick_update', 'quickUpdate');
        Route::delete('/delete', 'destroy');
        Route::post('/delete_multiple', 'deleteMultiple');
    });

    Route::group(
        ['prefix' => 'weighted/fluctuation', 'controller' => WeightedFluctuationController::class],
        function () {
            Route::get('/list', 'list');
            Route::get('/get_leader_board', 'getLeaderBoard');
        }
    );

    Route::group(['prefix' => 'petition', 'controller' => PetitionController::class], function () {
        Route::get('/get_selectboxes', 'getSelectboxes');
        Route::post('/get_petition_list', 'getPetitionList');
        Route::get('/get_petition_by_id', 'getPetitionById');
        Route::post('/check_petition_infringe', 'checkPetitionInfringe');
        Route::post('/store', 'store');
        Route::patch('/action', 'action');
        Route::patch('/update', 'update');
        Route::delete('/delete', 'destroy');
        Route::patch('/update_approve_pm', 'updateApprovePm');
    });

    Route::group(['prefix' => 'timesheet', 'controller' => TimesheetController::class], function () {
        Route::post('/get_timesheet_list', 'getTimesheetList');
        Route::post('/get_report', 'getReport');
        Route::get('/get_session', 'getSession');
        Route::post('/update_checkout', 'updateCheckOut');
    });

    Route::group(['prefix' => 'timesheet/detail', 'controller' => TimesheetDetailController::class], function () {
        Route::get('/get_timesheet_detail', 'getTimesheetDetail');
        Route::post('/sync', 'sync');
    });

    Route::group(['prefix' => 'holiday', 'controller' => HolidayController::class], function () {
        Route::get('/get_holidays', 'getHolidays');
        Route::post('/store', 'store');
        Route::patch('/update', 'update');
        Route::delete('/delete', 'destroy');
    });

    Route::group(['prefix' => 'holiday_offsets', 'controller' => HolidayOffsetController::class], function () {
        Route::get('/list', 'getHolidayOffsets');
        Route::post('/store', 'store');
        Route::patch('/update', 'update');
        Route::delete('/delete', 'destroy');
    });

    Route::group(['prefix' => 'device', 'controller' => TrackingDeviceController::class], function () {
        Route::get('/get_devices', 'getDevices');
        Route::get('/sync_devices', 'syncDevices');
        Route::get('/get_devices_info', 'getDevicesInfo');
    });

    Route::group(['prefix' => 'partner', 'controller' => PartnerConfigController::class], function () {
        Route::get('/get_config', 'getPartnerConfig');
        Route::get('/get_places', 'getPlaces');
        Route::post('/register_employee_face_id', 'registerEmployee');
        Route::post('/update_employee_face_id', 'updateEmployeeFaceID');
        Route::get('/sync_employee', 'syncEmployees');
        Route::delete('/delete', 'destroy');
    });

    Route::group(['prefix' => 'priority', 'controller' => PriorityController::class], function () {
        Route::get('/get_priorities', 'getPriorities');
        Route::patch('/update', 'update');
        Route::post('/store', 'store');
        Route::delete('/delete', 'destroy');
    });

    Route::group(['prefix' => 'sticker', 'controller' => StickerController::class], function () {
        Route::get('/get_stickers', 'getStickers');
        Route::get('/get_departments', 'getDepartments');
        Route::patch('/update', 'update');
        Route::patch('/quick_update', 'quickUpdate');
        Route::post('/store', 'store');
        Route::delete('/delete', 'destroy');
    });

    Route::group(['prefix' => 'announcements', 'controller' => PostController::class], function () {
        Route::get('/list', 'getListPosts');
        Route::get('/get_post_by_id', 'getPostById');
        //show latest announcements to all employees
        Route::get('/latest', 'latest');
        //end
        Route::post('/store', 'store');
        Route::patch('/update', 'update');
        Route::delete('/delete', 'destroy');
    });

    Route::group(['prefix' => 'journal', 'controller' => JournalController::class], function () {
        Route::get('/get_selbox', 'getSelbox');
        Route::get('/get_journals', 'getJournals');
        Route::get('/get_journal_by_id', 'getJournalById');
        Route::post('/store', 'store');
        Route::post('/update-order', 'updateOrder');
        Route::patch('/update', 'update');
        Route::delete('/delete', 'destroy');
    });

    Route::group(['prefix' => 'forum', 'controller' => ForumController::class], function () {
        Route::post('/redirectly', 'redirectly');
        Route::get('/get-latest-posts', 'getLatestPosts');
    });

    //export/import
    Route::group(['prefix' => 'task/export', 'controller' => TaskExportController::class], function () {
        Route::post('/', 'export');
    });
    Route::group([
        'prefix' => 'task_assignments/export',
        'controller' => TaskAssignmentExportController::class
    ], function () {
        Route::post('/', 'export');
    });
    Route::group(['prefix' => 'task/import', 'controller' => TaskImportController::class], function () {
        Route::post('/', 'import');
        Route::post('/check_excel_data', 'checkExcelData');
        Route::post('/import-job', 'importJob');
    });
    Route::group(
        [
            'prefix' => 'timesheet/report/export',
            'controller' => TimesheetReportExportController::class
        ],
        function () {
            Route::post('/', 'export');
        }
    );
    Route::group(['prefix' => 'sticker/export', 'controller' => StickerExportController::class], function () {
        Route::post('/', 'export');
    });
    Route::group(['prefix' => 'sticker/import', 'controller' => StickerImportController::class], function () {
        Route::post('/', 'import');
    });
    Route::group(['prefix' => 'report/export', 'controller' => ReportExportController::class], function () {
        Route::post('/', 'export');
    });
    Route::group(
        [
            'prefix' => 'wzandroid0022/import',
            'controller' => WZAndroid0022ImportController::class
        ],
        function () {
            Route::post('/', 'import');
        }
    );
    Route::group(
        [
            'prefix' => 'wzandroid0022/export',
            'controller' => WZAndroid0022ExportController::class
        ],
        function () {
            Route::post('/', 'export');
        }
    );
    //end

    Route::group(['prefix' => 'wz_android_0022', 'controller' => WZAndroid0022Controller::class], function () {
        Route::get('/get_selboxes', 'getSelboxes');
        Route::post('/get_tracking_in_out', 'getTrackingInOut');
        Route::post('/get_group', 'getGroup');
    });

    Route::group(['prefix' => 'calendar_event', 'controller' => CalendarEventController::class], function () {
        Route::get('/get_selectboxes', 'getSelectboxes');
        Route::get('/get_event_list', 'getEventList');
        Route::post('/store_config', 'storeConfig');
        Route::patch('/quick_update_config', 'quickUpdateConfig');
        Route::post('/delete_config', 'destroyConfig');

        Route::post('/store_calendar', 'storeCalendar');
        Route::post('/update_calendar', 'updateCalendar');
        Route::post('/destroy_calendar', 'destroyCalendar');

        Route::post('/get_calendar', 'getCalendar');
        Route::post('/get_calendar_detail', 'getCalendarDetail');
        Route::post('/get_calendar_by_id', 'getCalendarById');

        Route::post('/buzz_calendar', 'onBuzzCalendar');

    });
    Route::group(['prefix' => 'tracking_game', 'controller' => TrackingGameController::class], function () {
        Route::get('/get_tracking_game', 'getTrackingGame');
    });
    Route::group(['prefix'=>'order/stores','controller'=>OrderStoreController::class], function(){
        Route::get('/','getListOrderStore');
        Route::post('/','store')->middleware(['checkPermission']);
        Route::get('/{id}','show')->middleware(['checkPermission']);
        Route::put('/{id}','update')->middleware(['checkPermission']);
        Route::delete('/{id}','destroy')->middleware(['checkPermission']);
    });

    Route::group(['prefix'=>'order/setting','controller'=>OrderSettingController::class], function(){
        Route::post('/','save')->middleware(['checkPermission']);
        Route::get('/','show')->middleware(['checkPermission']);
        Route::get('/current','getCurrentSetting');
    });

    Route::group(['prefix'=>'order','controller'=>OrderController::class], function(){
        Route::post('/','store');
        Route::put('/{id}','update');
        Route::get('/','getListOrder');
        Route::patch('/quick-update', 'quickUpdate');
        Route::patch('/order-user/quick-update', 'quickUpdateOrderUser');
        Route::patch('user/quick-update','quickUpdateUserAlias');
        Route::get('/{order_id}','show');
        Route::delete('/{order_id}','destroy');
        Route::get('/payment/is-paid','checkUserIsPaidPayment');
    });

    Route::group(['prefix'=>'order','controller'=>OrderStatistialController::class], function(){
        Route::get('/statistial/week','statistialOrderWeek');
    });

    Route::group(['prefix'=>'statistial','controller'=>StatistialController::class], function(){
        Route::get('/top','statistialTop');
        Route::get('/top/warrior','statistialTopWarriorYear');
    });

    Route::group(['prefix'=>'statistial/export','controller'=>StatistialTopExportController::class], function(){
        Route::post('/top','exportStatistialTop');
    });

    Route::group(['prefix'=>'employee/list/export','controller'=>EmployeeListExportController::class], function(){
        Route::post('/','export');
    });
    Route::group(['prefix'=>'employee/list/export','controller'=>EmployeeListExportController::class], function(){
        Route::post('/','export');
    });
    Route::group(['prefix'=>'purchase','controller'=>PurchaseController::class], function(){
        Route::get('/get_selectboxes','getSelectboxes');
        
        Route::get('/get_purchase','getPurchase');
        Route::get('/get_purchase_by_id','getPurchaseById');
        Route::post('/store','store');
        Route::patch('/update','update');
        Route::delete('/delete','delete');
        
        Route::get('/get_purchase_supplier_by_id','getPurchaseSupplierById');
        Route::get('/get_company_data','getCompanySupplier');
        Route::post('/store_supplier','storeSupplier');
        Route::patch('/update_supplier','updateSupplier');
        Route::post('/update_supplier_status','updateSupplierStatus');
        Route::delete('/delete_supplier','deleteSupplier');
    });
    Route::group(['prefix'=>'working_time','controller'=>WorkingTimeController::class], function(){
        Route::get('/get_working_time','getWorkingTime');
        Route::post('/import_salary','getImportData');
        Route::post('/download_salary','getTemplateSalary');
        Route::post('/export','export');
        
    });
    Route::group(['prefix'=>'management/company','controller'=>CompanyController::class], function(){
        Route::get('/get_company','getCompany');
        Route::get('/get_company_by_id','getCompanyById');
        Route::post('/store','store');
        Route::patch('/update','update');
        Route::delete('/delete','delete');
        
    });
    Route::group(['prefix'=>'management/department','controller'=>DepartmentController::class], function(){
        Route::get('/get_selectboxes','getSelectboxes');
        Route::get('/get_department','getDepartment');
        Route::get('/get_department_by_id','getDepartmentById');
        Route::post('/store','store');
        Route::patch('/update','update');
        Route::patch('/quick_update','quickUpdate');
        Route::delete('/delete','delete');
        
    });

    Route::group(['prefix'=>'warrior','controller'=>WarriorProjectController::class], function(){
        Route::get('/get_selectboxes','getSelectboxes');
        Route::post('/get_warrior_project','getWarriorProject');
        Route::post('/export','export');
        
    });
});

Route::group(['prefix' => 'employee', 'controller' => EmployeeController::class], function () {
    Route::post('/checkin_data', 'checkin');
});
