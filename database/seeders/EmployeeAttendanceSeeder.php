<?php

namespace Database\Seeders;

use App\Models\VisitorAttendance;
use Illuminate\Database\Seeder;

class EmployeeAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $arr = [];

        // Define the start and end dates for July
        $startDate = '2023-08-01';
        $endDate = '2023-08-31';

        $statuses = ['A', 'P', 'M', 'O', 'ME', 'L', 'E', 'V', 'H'];

        $currentDate = $startDate;


        while ($currentDate <= $endDate) {
            $in = $this->generateRandomTime('09:30');
            $out = $this->generateRandomTime('16:30');

            $arr[]  = [
                'date' => $currentDate,
                'visitor_id' => 6666,
                'status' => $statuses[array_rand($statuses)],
                'in' => $in,
                'out' => $out,
                'total_hrs' => $this->calculateTotalHours($in, $out),
                'device_id_in' => "OX-8862021010010",
                'device_id_out' => "OX-8862021010010",
                'date_in' => $currentDate,
                'date_out' => $currentDate,
                'company_id' => 8, // As
            ];


            // Move to the next day
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }
        // php artisan db:seed --class=VisitorAttendanceSeeder
        $model = VisitorAttendance::query();
        // $model->where("visitor_id", $this->visitor_id);
        $model->delete();
        $model->insert($arr);
    }

    public function generateRandomTime($baseTime)
    {
        // Generate random minutes between 0 and 210 (3.5 hours in minutes)
        $randomMinutes = mt_rand(0, 210);

        // Add the random minutes to the base time (9:30)
        $baseTime = strtotime($baseTime);
        $randomTime = strtotime("+$randomMinutes minutes", $baseTime);

        // Format the time to H:i format
        return date('H:i', $randomTime);
    }


    public function calculateTotalHours($inTime, $outTime)
    {
        // Convert 'in' and 'out' times to timestamps
        $inTimestamp = strtotime($inTime);
        $outTimestamp = strtotime($outTime);

        $diff = $outTimestamp - $inTimestamp;

        $h = floor($diff / 3600);
        $m = floor(($diff % 3600) / 60);
        return (($h < 10 ? "0" . $h : $h) . ":" . ($m < 10 ? "0" . $m : $m));
    }
}
