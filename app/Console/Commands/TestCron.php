<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log as Logger;


class TestCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test_cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'test cron';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $date = date("Y-m-d H:i:s");
        $script_name = "TestCron";

        $meta = "[$date] Cron: $script_name.";

        try {
            echo $meta . " Testing Cron...\n.";
        } catch (\Throwable $th) {
            Logger::channel("custom")->error('Cron: TestCron. Error Details: ' . $th);
            echo "[" . date("Y-m-d H:i:s") . "] Cron: TestCron. Error occurred while inserting logs.\n";
        }
    }
}
