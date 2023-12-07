<?php

namespace App\Console\Commands;

use App\Http\Controllers\AttendanceLogController;
use Illuminate\Console\Command;

class SyncAttendanceLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:sync_attendance_logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Attendance Logs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        echo (new AttendanceLogController)->store();
    }
}
