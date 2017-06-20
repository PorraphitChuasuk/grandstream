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

        $schedule->call(function() {
            cron_update_grandstream_recordfiles();
        })->hourlyAt(5)->between('04:00', '23:00');

        $schedule->call(function() {
            cron_update_mapping_pipedrive();
        })->hourlyAt(10)->between('04:00', '23:00');

        $schedule->call(function() {
            cron_post_pipedrive();
        })->hourlyAt(15)->between('04:00', '23:00');

        $schedule->call(function() {
            cron_reset_mapping_table();
        })->dailyAt('01:00');

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
