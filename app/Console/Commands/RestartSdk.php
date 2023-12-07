<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log as Logger;


class RestartSdk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'restart_sdk';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'to restart sdk';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            exec('pm2 restart all');
            echo "SDK restarted successfully\n";
        } catch (\Throwable $th) {
            Logger::channel("custom")->error('Cron: RestartSdk. Error Details: ' . $th);
            echo "[" . date("Y-m-d H:i:s") . "] Cron: RestartSdk. Error occurred while inserting logs.\n";
        }
    }
}
