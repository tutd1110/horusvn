<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\CreateTasksFromPending;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

use App\Models\Calendar;
use App\Events\PrivateWebSocket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('save_log_check_out:cron')->daily()->timezone('Asia/Ho_Chi_Minh');
        // $schedule->command('insert_log_check_in:cron')->everyMinute();
        // $schedule->command('update_tasks_weighted:cron')->everyMinute();
        // $schedule->command('move_task_files:cron')->everyMinute();

        // $schedule->command('update_tasks_out_date:cron')->timezone('Asia/Ho_Chi_Minh')->hourly();

        // Authenticate the user
        $user = User::where('email', 'bienvq@horusvn.com')->first();
        Auth::login($user);
        $schedule->job(new CreateTasksFromPending)->daily()->timezone('Asia/Ho_Chi_Minh')->user($user);

        $schedule->call(function () {
            $eventsToNotify = Calendar::select(
                'calendars.id',
                'calendars.name',
                'calendars.start_time',
                'calendars.end_time',
                'calendars.date',
                'calendars.event_id',
                'calendars.department_id',
                'calendars.user_created',
                DB::raw("array_agg(DISTINCT interviewers.user_id) as user_select")
                )
            ->leftJoin('calendar_events', 'calendar_events.id', '=', 'calendars.event_id')
            ->leftJoin('interviewers', function ($join) {
                $join->on('interviewers.calendar_id', '=', 'calendars.id')
                        ->whereNull('interviewers.deleted_at');
            })
            ->where('calendars.date',Carbon::today()->format('Y-m-d'))
            ->whereTime('calendars.start_time', '>=', Carbon::now()->addMinutes(5))
            ->whereTime('calendars.start_time', '<', Carbon::now()->addMinutes(6))
            ->whereNull('calendars.deleted_at')
            ->groupBy('calendars.id', 'calendars.name', 'calendars.start_time', 'calendars.end_time', 'calendars.date', 'calendars.event_id', 'calendars.department_id', 'calendars.user_created')
            ->get();

            if ($eventsToNotify) {
                broadcast(new PrivateWebSocket(
                    $eventsToNotify[0]['name'],
                    // [$eventsToNotify[0]['user_created']],
                    array_merge(explode(",", trim($eventsToNotify[0]['user_select'], "{}")), [$eventsToNotify[0]['user_created']]),
                    'calendar'
                ))->toOthers();
            }

        })->everyMinute()->timezone('Asia/Ho_Chi_Minh');

        $schedule->command('payment:noti')->cron('58 09 * * FRI')->timezone('Asia/Ho_Chi_Minh');
        $schedule->command('orderSetting:disable')->everyMinute()->timezone('Asia/Ho_Chi_Minh');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
