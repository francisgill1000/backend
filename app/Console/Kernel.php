<?php

namespace App\Console;

use App\Models\Company;
use App\Models\PayrollSetting;
use App\Models\ReportNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

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
        $date = date("M-Y");

        if (env("APP_ENV") !== "local") {

            $schedule
                ->command('task:sync_attendance_logs')
                ->everyMinute()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path("logs/$date-attendance-logs.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            $schedule
                ->command('task:check_mismatch_count')
                 ->dailyAt('5:00')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path("logs/$date-mismatch-logs.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            $schedule
                ->command('task:update_company_ids')
                // ->everyThirtyMinutes()
                ->everyMinute()
                ->withoutOverlapping()
                // ->between('7:00', '23:59')
                ->appendOutputTo(storage_path("logs/$date-logs.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            $schedule
                ->command('task:sync_all_shifts')
                // ->dailyAt('4:00')
                // ->hourly()
                ->everyMinute()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path("logs/$date-logs.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            $schedule
                ->command('task:sync_filo_shift')
                // ->dailyAt('4:00')
                // ->hourly()
                ->everyMinute()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path("logs/$date-logs.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            $schedule
                ->command('task:sync_multiinout')
                // ->dailyAt('4:00')
                // ->hourly()
                ->everyMinute()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path("logs/$date-logs.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            $schedule
                ->command('task:update_visitor_company_ids')
                // ->everyThirtyMinutes()
                ->everyMinute()
                ->withoutOverlapping()
                // ->between('7:00', '23:59')
                ->appendOutputTo(storage_path("logs/$date-logs.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            $schedule
                ->command('task:sync_visitors')
                ->everyMinute()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path("logs/$date-visitor-logs.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            $schedule
                ->command('task:check_device_health')
                ->everyThirtyMinutes()
                ->withoutOverlapping()
                ->appendOutputTo(storage_path("logs/$date-devices-health.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            // PDF
            $schedule
                ->command('task:generate_summary_report')
                // ->everyMinute()
                // ->everyThirtyMinutes()
                ->dailyAt('2:00')
                ->runInBackground()
                //->hourly()
                ->appendOutputTo(storage_path("logs/pdf.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            $schedule
                ->command('task:generate_daily_present_report')
                // ->everyMinute()
                // ->everyThirtyMinutes()
                ->dailyAt('2:00')
                ->runInBackground()
                //->hourly()
                ->appendOutputTo(storage_path("logs/pdf.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            $schedule
                ->command('task:generate_daily_absent_report')
                // ->everyMinute()
                // ->everyThirtyMinutes()
                ->dailyAt('2:00')
                ->runInBackground()
                //->hourly()
                ->appendOutputTo(storage_path("logs/pdf.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            $schedule
                ->command('task:generate_daily_missing_report')
                // ->everyMinute()
                // ->everyThirtyMinutes()
                ->dailyAt('2:00')
                ->runInBackground()
                //->hourly()
                ->appendOutputTo(storage_path("logs/pdf.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            $schedule
                ->command('task:generate_daily_manual_report')
                // ->everyMinute()
                // ->everyThirtyMinutes()
                ->dailyAt('2:00')
                ->runInBackground()
                //->hourly()
                ->appendOutputTo(storage_path("logs/pdf.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            $schedule
                ->command('task:assign_schedule_to_employee')
                // ->everyThirtyMinutes()
                // ->everyMinute()
                ->dailyAt('1:30')
                ->runInBackground()
                ->withoutOverlapping()
                // ->between('7:00', '23:59')
                ->appendOutputTo(storage_path("logs/$date-assigned-schedule-emplyees.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            $payroll_settings = PayrollSetting::get(["id", "date", "company_id"]);

            foreach ($payroll_settings as $payroll_setting) {

                $payroll_date = (int) (new \DateTime($payroll_setting->date))->modify('-24 hours')->format('d');

                $schedule
                    ->command("task:payslip_generation $payroll_setting->company_id")
                    ->monthlyOn((int) $payroll_date, "00:00")
                    ->appendOutputTo(storage_path("$date-payslip-generate-$payroll_setting->company_id.log"))
                    ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));
            }

            $companyIds = Company::pluck("id");

            foreach ($companyIds as $companyId) {

                $schedule
                    ->command("task:sync_absent $companyId")
                    // ->everyMinute()
                    ->dailyAt('00:30')
                    ->runInBackground()
                    ->appendOutputTo(storage_path("logs/$date-absents-$companyId.log"))
                    ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

                $schedule
                    ->command("task:sync_leaves $companyId")
                    //->everyFiveMinutes()
                    ->dailyAt('02:00')
                    ->runInBackground()
                    ->appendOutputTo(storage_path("logs/$date-leaves-$companyId.log"))
                    ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

                $schedule
                    ->command("task:sync_holidays $companyId")
                    //->everyTenMinutes()
                    ->dailyAt('03:00')
                    ->runInBackground()
                    ->appendOutputTo(storage_path("logs/$date-holidays-$companyId.log"))
                    ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

                $schedule
                    ->command("task:sync_off $companyId")
                    // ->everyMinute()
                    ->dailyAt('2:30')
                    ->runInBackground()
                    ->appendOutputTo(storage_path("logs/$date-offs-$companyId.log"))
                    ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));
            }
        }

        if (env("APP_ENV") == "production") {
            $schedule
                ->command('task:db_backup')
                ->dailyAt('6:00')
                ->appendOutputTo(storage_path("logs/db_backup.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            $schedule
                ->command('restart_sdk')
                // ->everyMinute()
                // ->everyThirtyMinutes()
                ->dailyAt('4:00')
                //->hourly()
                ->appendOutputTo(storage_path("logs/restart_sdk.log"))
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));
        }


        // ReportNotification

        $models = ReportNotification::get();

        foreach ($models as $model) {
            $scheduleCommand = $schedule->command('task:report_notification_crons')
                ->runInBackground()
                ->appendOutputTo("custom_cron.log")
                ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));

            if ($model->frequency == "Daily") {
                $scheduleCommand->dailyAt($model->time);
            } elseif ($model->frequency == "Weekly") {
                $scheduleCommand->weeklyOn($model->day, $model->time);
            } elseif ($model->frequency == "Monthly") {
                $scheduleCommand->monthlyOn($model->day, $model->time);
            }
        }
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
