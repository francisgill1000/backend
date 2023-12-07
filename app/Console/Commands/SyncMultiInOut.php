<?php

namespace App\Console\Commands;

use App\Http\Controllers\Shift\MultiInOutShiftController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log as Logger;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifyIfLogsDoesNotGenerate;


class SyncMultiInOut extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:sync_multiinout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync MultiInOut';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $date = date("Y-m-d H:i:s");
        $script_name = "SyncMultiInOut";

        $meta = "[$date] Cron: $script_name.";

        try {
            $Attendance = new MultiInOutShiftController;
            $result = $Attendance->syncLogsScript();
            $message =  $meta . " " . $result . ".\n";
            echo $message;
            return;
        } catch (\Throwable $th) {
            Logger::channel("custom")->error('Cron: SyncMultiInOut. Error Details: ' . $th);
            echo "[$date] Cron: $script_name. Error occured while inserting logs.\n";
            return;
        }
    }
}
