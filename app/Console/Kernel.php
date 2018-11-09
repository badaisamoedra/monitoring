<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\SyncMasterVehicleRelated',
        'App\Console\Commands\SyncMasterEventRelated',
        'App\Console\Commands\SyncMasterStatusEvent',
        'App\Console\Commands\SyncMasterStatusVehicle',
        'App\Console\Commands\GpsNotUpdateOneDay',
        'App\Console\Commands\GpsNotUpdateThreeDay',
        'App\Console\Commands\GpsNotUpdateThreeDay',
        'App\Console\Commands\SyncBlackBox',
        'App\Console\Commands\SyncAddress',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('master_vehicle_related:sync')->everyFifteenMinutes();
        $schedule->command('master_event_related:sync')->everyFiveMinutes();
        $schedule->command('master_status_event:sync')->everyFiveMinutes();
        $schedule->command('master_status_vehicle:sync')->everyFiveMinutes();
        $schedule->command('gps_not_update_one_day:sync')->hourly();
        $schedule->command('gps_not_update_three_day:sync')->hourly();
        $schedule->command('get_black_box:sync')->daily();
        $schedule->command('sync_address:sync')->daily();
        $schedule->command('log_integration:sync')->cron('0 */2 * * *'); // every 2 hours
    }
}
