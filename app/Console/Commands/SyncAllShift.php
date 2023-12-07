<?php

namespace App\Console\Commands;

use App\Http\Controllers\Shift\SingleShiftController;
use App\Http\Controllers\Shift\MultiInOutShiftController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log as Logger;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifyIfLogsDoesNotGenerate;


class SyncAllShift extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:sync_all_shifts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync All Shift';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            echo (new SingleShiftController)->syncLogsScript();
            // echo (new MultiInOutShiftController)->syncLogsScript();
        } catch (\Throwable $th) {
            Logger::channel("custom")->error('Cron: SyncAllShift. Error Details: ' . $th);
            $date = date("Y-m-d H:i:s");
            echo "[$date] Cron: SyncAllShift. Error occured while inserting logs.\n";
        }
    }
}
