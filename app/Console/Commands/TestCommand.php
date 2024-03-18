<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test-me';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // $path = $this->ask('Enter the path to the database file (SQLite)');

        // $contents = File::get(base_path('.env'));

        // // Update the database path in the .env file
        // $updatedContents = preg_replace('/DB_TEST=(.*)/', 'DB_TEST=' . $path, $contents);

        // File::put(base_path('.env'), $updatedContents);

        // $this->info('Database configuration updated successfully!');

        // $p = $this->secret('What is the password?');

        if ($this->confirm('Do you wish to continue?')) {
          echo "sdfsdf";
        }




        // echo $p;
        // echo $path;

    }
}
