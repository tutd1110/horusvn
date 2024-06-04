<?php

namespace App\Http\Controllers;

use App\Http\Controllers\api\CommonController;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ScreenController extends Controller
{
    /**
     * constructor
     */
    public function __construct(Router $router)
    {
        $action = null;

        if (empty($router->getCurrentRoute()->action)) {
            $action = 'ZZZZZZZ';
        } else {
            //get function ID (call method name)
            $action = $router->getCurrentRoute()->action["as"];
        }

        //skip through the login screen
        if ($action === 'login') {
            return;
        }
        
        $this->middleware(function ($request, $next) use ($action) {
            //get login user
            $loginUser = \Auth::user();
            //authority check
            \ProcAuthority::checkAuthority($action, $loginUser);
            return $next($request);
        });
    }

    /**
     * Login screen
     */
    public function login()
    {
        return view('login.login');
    }

    /**
     * Home screen
     */
    public function home()
    {
        return view('home.home');
    }

    /**
     * Log Route screen
     */
    public function log()
    {
        return view('log.log');
    }

    /**
     * Employee screen
     */
    public function employee()
    {
        return view('employee.employee');
    }

    public function employeeWorkdayReport()
    {
        return view('employee.employee-workday-report');
    }

    /**
     * Employee Review screen
     */
    public function employeeWithReview()
    {
        return view('employee.review');
    }

    /**
     * Employee Review List screen
     */
    public function employeeReviewList()
    {
        return view('employee.review_list');
    }

    /**
     * Employee Review Personal screen
     */
    public function employeeReviewPersonal()
    {
        return view('employee.review_personal');
    }

    /**
     * Report screen
     */
    public function report()
    {
        return view('report.report');
    }

    /**
     * Project screen
     */
    public function projects()
    {
        return view('projects.project');
    }

    /**
     * All tasks screen
     */
    public function tasks()
    {
        return view('tasks.task');
    }

    /**
     * Deadline Modification Request screen
     */
    public function deadlineModification()
    {
        return view('tasks.deadline-modification');
    }

    /**
     * Department with all tasks screen
     */
    public function departmentWithTasks()
    {
        return view('departments.task');
    }

    /**
     * My tasks screen
     */
    public function meWithTasks()
    {
        return view('me.task');
    }

    /**
     * Weighted Fluctuation screen
     */
    public function weightedFluctuation()
    {
        return view('weighted.fluctuation');
    }

    /**
     * Petition screen
     */
    public function petitions()
    {
        return view('petitions.petition');
    }

    /**
     * Timesheet screen
     */
    public function timesheets()
    {
        return view('timesheets.timesheet');
    }

    /**
     * Timesheet report screen
     */
    public function timesheetWithReport()
    {
        return view('timesheets.report');
    }

    /**
     * Announcements manager post screen
     */
    public function post()
    {
        return view('announcements.post');
    }

    /**
     * Announcements post list screen
     */
    public function postList()
    {
        return view('announcements.list');
    }

    /**
     * Journal screen
     */
    public function journalCompany()
    {
        return view('journal.company');
    }
    public function journalDepartment()
    {
        return view('journal.department');
    }
    public function journalGame()
    {
        return view('journal.game');
    }

    /**
     * Issues screen
     */
    public function departmentSelfCreated()
    {
        return view('Issues.Department.SelfCreated');
    }
    public function departmentAssigned()
    {
        return view('Issues.Department.Assigned');
    }
    public function personalSelfCreated()
    {
        return view('Issues.Personal.SelfCreated');
    }
    public function personalAssigned()
    {
        return view('Issues.Personal.Assigned');
    }

    public function previewReview(Request $request)
    {
        try {
            if (\Auth::user()->department_id != 7 && !in_array(\Auth::user()->id, [51,107,161,232])) {
                return redirect()->route('home');
            }
            //on request
            $requestDatas = $request->all();

            $data = CommonController::loadReviewData($requestDatas);

            $pdf = Pdf::loadView('employee.preview_review', $data);

            return $pdf->stream($data['employee']->fullname.'.pdf');
        } catch (Exception $e) {
            Log::error($e);
            return redirect()->route('home');
        }
    }

    public function calendar()
    {
        return view('calendar.calendar');
    }

    public function taskGantt()
    {
        return view('tasks.gantt');
    }
    
    public function trackingGame()
    {
        return view('tracking.tracking-game');
    }
    
    public function order()
    {
        return view('order.index');
    }
    public function statistial()
    {
        return view('statistial.statistial');
    }
    
    public function purchase()
    {
        return view('purchase.purchase');
    }

    public function workingTime()
    {
        return view('working.working_time');
    }

    public function company()
    {
        return view('management.company');
    }
    public function department()
    {
        return view('management.department');
    }
    
    public function warrior()
    {
        return view('warrior.warrior');
    }
}
