<?php

namespace App\Console\Commands;

use App\Http\Controllers\Shift\FiloShiftController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log as Logger;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifyIfLogsDoesNotGenerate;


class SyncFiloShift extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:sync_filo_shift';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Filo Shift';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            echo (new FiloShiftController)->syncLogsScript();
        } catch (\Throwable $th) {
            Logger::channel("custom")->error('Cron: SyncFiloShift. Error Details: ' . $th);
            $date = date("Y-m-d H:i:s");
            echo "[$date] Cron: SyncFiloShift. Error occured while inserting logs.\n";
        }
    }
}
