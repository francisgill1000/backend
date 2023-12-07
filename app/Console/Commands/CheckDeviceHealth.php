<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;

// use Illuminate\Support\Facades\Log as Logger;
// use Illuminate\Support\Facades\Mail;
// use App\Mail\NotifyIfLogsDoesNotGenerate;

class CheckDeviceHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:check_device_health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Device Health';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $devices = Device::pluck("device_id");

        $total_iterations = 0;
        $online_devices_count = 0;
        $offline_devices_count = 0;

        $sdk_url = '';

        if ($sdk_url == '') {
            $sdk_url = "http://139.59.69.241:5000";
        }
        if (env("APP_ENV") != "production") {
            $sdk_url = env("SDK_STAGING_COMM_URL");
        }

        if (checkSDKServerStatus($sdk_url) === 0) {
            $date = date("Y-m-d H:i:s");
            echo "[$date] Cron: CheckDeviceHealth. SDK Server is down.\n";
            return;
        }

        foreach ($devices as $device_id) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "$sdk_url/CheckDeviceHealth/$device_id",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            if (json_decode($response)) {
                $status = json_decode($response);

                if ($status && $status->status == 200) {
                    $online_devices_count++;
                } else {
                    $offline_devices_count++;
                }

                Device::where("device_id", $device_id)->update(["status_id" => $status->status == 200 ? 1 : 2]);

                $total_iterations++;
            } else {
                echo "Error\n";
            }
        }

        $date = date("Y-m-d H:i:s");
        $script_name = "CheckDeviceHealth";

        $meta = "[$date] Cron: $script_name.";

        $result = "$offline_devices_count Devices offline. $online_devices_count Devices online. $total_iterations records found";

        $message = $meta . " " . $result . ".\n";
        echo $message;
    }

    public function checkSDKServerStatus($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return $httpCode;
    }
}
