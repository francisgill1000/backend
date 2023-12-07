<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class WhatsappController extends Controller
{
    public function api1($data)
    {
        $url = "https://messages-sandbox.nexmo.com/v1/messages";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: Basic NmU3MzVjYzA6ZU5UeXd3N1BuMTcyM3RQSg==",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);


        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);
        return $resp;
    }

    public function SendNotification(Request $request)
    {

        $device = Device::where("device_id", $request->DeviceID)->first(["company_id"]);

        $model = Employee::query();

        $model->withOut(["department"]);
        $model->whereHas("schedule", function ($q) {
            $q->where('shift_type_id', 6);
        });
        $model->where("company_id", $device->company_id);
        $model->where("system_user_id", $request->UserID);
        $found  = $model->first(["display_name", "phone_number", "system_user_id", "employee_id"]);

        if (!$found) {
            return response()->noContent();
        }

        $shift = $found->schedule->shift;
        $time = date('H:i', strtotime($request->LogTime));
        $late = $this->calculatedLateComing($time, $shift->on_duty_time, $shift->late_time);

        $data = [
            "from"          => "14157386102",
            "message_type"  => "text",
            "channel"       => "whatsapp",
            "to"            => "971502848071",
            "text"          => "testing...",
            // "to" => $request->to,
            // "text" => $request->text,
        ];

        return $late ? $this->api($data) : "keep sleeping";
    }

    private function api($data)
    {
        $url = env('NEXMO_URL');
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => env('NEXMO_AUTHORIZATION'),
        ];

        $response = Http::withHeaders($headers)->post($url, $data);
        $data = $response->body();
        Storage::put('whatsapp.txt', $data);
        return $data;
    }


    public function calculatedLateComing($time, $on_duty_time, $grace)
    {
        $interval_time = date("i", strtotime($grace));

        $late_condition = strtotime("$on_duty_time + $interval_time minute");

        $in = strtotime($time);

        if ($in > $late_condition && $grace != "---") {
            return true;
        }

        return false;
    }
}
