<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        /*
        $schedule->call(function() {
            ini_set('max_execution_time', 300);

            $pipedrive = new \App\Library\Pipedrive;
            $pipedrive->update_deal(\App\Library\Pipedrive::THAILAND, 0);
            $pipedrive->update_deal(\App\Library\Pipedrive::SINGAPORE, 0);

            finished('RESETTING DEAL TABLE');

        })->dailyAt('01:00');
        */

        $schedule->call(function() {
            ini_set('max_execution_time', 300);

            $grandstream = new \App\Library\Grandstream;
            $grandstream->add_recordfile();

            $pipedrive = new \App\Library\Pipedrive;
            $pipedrive->update_deal(\App\Library\Pipedrive::THAILAND);
            $pipedrive->update_deal(\App\Library\Pipedrive::SINGAPORE);

            $pipedrive->post_recording_file(\App\Library\Pipedrive::THAILAND);
            $pipedrive->post_recording_file(\App\Library\Pipedrive::SINGAPORE);

            finished('PUSHING CALLS');

        })->hourlyAt(30)->between('06:00', '20:00');

        $schedule->call(function() {
            ini_set('max_execution_time', 300);

            $grandstream = new \App\Library\Grandstream;
            $grandstream->update_cdr();

            finished('UPDATING CDR TABLE');

        })->hourlyAt(10)->between('06:00', '20:00');

    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
