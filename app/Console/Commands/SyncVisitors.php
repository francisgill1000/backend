<?php

namespace App\Console\Commands;

use App\Http\Controllers\VisitorAttendanceController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log as Logger;

class SyncVisitors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:sync_visitors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Visitors';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            echo (new VisitorAttendanceController)->syncVisitorsCron();
        } catch (\Exception $e) {
            Logger::channel("custom")->error('Cron: SyncVisitors. Error Details: ' . $e->getMessage());
        }
    }
}
