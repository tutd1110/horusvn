<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Controllers\ScreenController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => ['auth:sanctum', ContentSecurityPolicy::class]], function () {
    Route::get('/home', [ScreenController::class, 'home'])->name('home');
    Route::get('/', [ScreenController::class, 'home'])->name('home');
    Route::get('/log', [ScreenController::class, 'log'])->name('log');
    Route::get('/employee', [ScreenController::class, 'employee'])->name('employee');
    Route::get('/employee-workday-report', [ScreenController::class, 'employeeWorkdayReport'])->name('employee-workday-report');
    Route::get('/employee/review', [ScreenController::class, 'employeeWithReview'])->name('employeeWithReview');
    Route::get(
        '/employee/review/personal',
        [ScreenController::class, 'employeeReviewPersonal']
    )
    ->name('employeeReviewPersonal');
    Route::get('/employee/review/list', [ScreenController::class, 'employeeReviewList'])->name('employeeReviewList');
    Route::get('/report', [ScreenController::class, 'report'])->name('report');
    Route::get('/projects', [ScreenController::class, 'projects'])->name('projects');
    Route::group(['prefix' => 'tasks', 'controller' => ScreenController::class], function () {
        Route::get('/', [ScreenController::class, 'tasks'])->name('tasks');
        Route::get('/deadline-modification', [ScreenController::class, 'deadlineModification'])->name('deadline-modification');
    });
    Route::get('/department/tasks', [ScreenController::class, 'departmentWithTasks'])->name('departmentWithTasks');
    Route::get('/me/tasks', [ScreenController::class, 'meWithTasks'])->name('meWithTasks');
    Route::get('/weighted/fluctuation', [ScreenController::class, 'weightedFluctuation'])->name('weightedFluctuation');
    Route::get('/petitions', [ScreenController::class, 'petitions'])->name('petitions');
    Route::get('/timesheets', [ScreenController::class, 'timesheets'])->name('timesheets');
    Route::get('/timesheets/report', [ScreenController::class, 'timesheetWithReport'])->name('timesheetWithReport');
    Route::get('/announcements/post', [ScreenController::class, 'post'])->name('post');
    Route::get('/announcements', [ScreenController::class, 'postList'])->name('postList');
    Route::get('/preview_review', [ScreenController::class, 'previewReview'])->name('previewReview');
    Route::get('/journal', [ScreenController::class, 'journal'])->name('journal');
    Route::group(['prefix' => 'journal', 'controller' => ScreenController::class], function () {
        Route::get('/company', [ScreenController::class, 'journalCompany'])->name('journalCompany');
        Route::get('/department', [ScreenController::class, 'journalDepartment'])->name('journalDepartment');
        Route::get('/game', [ScreenController::class, 'journalGame'])->name('journalGame');
    });
    Route::group(['prefix' => 'issues', 'controller' => ScreenController::class], function () {
        Route::get('/department-self-created', [ScreenController::class, 'departmentSelfCreated'])->name('department-self-created');
        Route::get('/department-assigned', [ScreenController::class, 'departmentAssigned'])->name('department-assigned');

        Route::get('/personal-self-created', [ScreenController::class, 'personalSelfCreated'])->name('personal-self-created');
        Route::get('/personal-assigned', [ScreenController::class, 'personalAssigned'])->name('personal-assigned');
    });
    Route::get('/calendar', [ScreenController::class, 'calendar'])->name('calendar');
    Route::get('/task-gantt', [ScreenController::class, 'taskGantt'])->name('taskGantt');
    Route::get('/tracking-game', [ScreenController::class, 'trackingGame'])->name('TrackingGame');
    Route::get('/order', [ScreenController::class, 'order'])->name('order');
    Route::get('/statistial', [ScreenController::class, 'statistial'])->name('statistial');
    Route::get('/purchase', [ScreenController::class, 'purchase'])->name('purchase');

    Route::get('/working-time', [ScreenController::class, 'workingTime'])->name('working-time');
    
    Route::group(['prefix' => 'management', 'controller' => ScreenController::class], function () {
        Route::get('/company', [ScreenController::class, 'company'])->name('company');
        Route::get('/department', [ScreenController::class, 'department'])->name('department');

    });
    Route::group(['prefix' => 'warrior', 'controller' => ScreenController::class], function () {
        Route::get('/warrior-project', [ScreenController::class, 'warrior'])->name('warrior');

    });
});

//login screen
Route::get('/login', [ScreenController::class, 'login'])->name('login');

//authentication process
Route::post('/login', LoginController::class)->name('login');
Route::post('/logout', LogoutController::class)->name('logout');

//error page
Route::get('error/{code}', function ($code) {
    abort($code);
});
