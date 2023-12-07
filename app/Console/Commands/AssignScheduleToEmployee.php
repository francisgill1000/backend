<?php

namespace App\Console\Commands;

use App\Http\Controllers\ScheduleEmployeeController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log as Logger;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifyIfLogsDoesNotGenerate;


class AssignScheduleToEmployee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:assign_schedule_to_employee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign Schedule To Employee';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            echo (new ScheduleEmployeeController)->assignSchedule() . "\n";
        } catch (\Throwable $th) {
            $date = date("Y-m-d H:i:s");
            $script_name = "AssignScheduleToEmployee";
            Logger::channel("custom")->error('Cron: AssignScheduleToEmployee. Error Details: ' . $th);
            echo "[$date] Cron: $script_name. Error occured while inserting logs.\n";
            return;
        }
    }
}
