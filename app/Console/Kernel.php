<?php

namespace App\Console;

use anlutro\LaravelSettings\Facade as Setting;
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
        Commands\UpdateMemberDetails::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Clean IP addresses and user agent information from old logs and very old logs
        $schedule->command('clean:logs')
            ->daily();

        // Daily fetch updated member data from OAuth provider
        $schedule->command('update:member:data')
            ->daily();

        // Automaticaly clean memebers and trainings no longer eligble
        $schedule->command('update:member:details')
            ->dailyAt('05:00');

        // Expire workmail addresses
        $schedule->command('update:workmails')
            ->daily();

        // Send task notifications
        $schedule->command('send:task:notifications')
            ->hourly();

        // Send telemetry data
        if (Setting::get('telemetryEnabled')) {
            $schedule->command('send:telemetry')
                ->daily();
        }

        // Check if updates are available
        $schedule->command('check:update')
            ->hourly();

        // Log last cronjob time
        $schedule->call(function () {
            Setting::set('_lastCronRun', now());
            Setting::save();
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
