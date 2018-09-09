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
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('master_vehicle_related:sync')->hourlyAt(180);
        $schedule->command('master_event_related:sync')->everyThirtyMinutes();
        $schedule->command('master_status_event:sync')->everyThirtyMinutes();
        $schedule->command('master_status_vehicle:sync')->everyThirtyMinutes();
    }
}
