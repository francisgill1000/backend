<?php

namespace App\Console\Commands;

use App\Http\Controllers\Shift\RenderController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log as Logger;

class SyncAbsent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:sync_absent {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Absent';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = $this->argument('id');

        try {
            echo (new RenderController)->renderAbsentCron($id);
        } catch (\Throwable $th) {
            Logger::channel("custom")->error('Cron: SyncAbsent. Error Details: ' . $th);
            echo "[" . date("Y-m-d H:i:s") . "] Cron: SyncAbsent. Error occurred while inserting logs.\n";
        }
    }
}
