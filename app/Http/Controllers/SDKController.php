<?php

namespace App\Http\Controllers;

use App\Jobs\TimezonePhotoUploadJob;
use App\Models\Device;
use App\Models\Timezone;
use App\Models\TimezoneDefaultJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SDKController extends Controller
{
    public function processTimeGroup(Request $request, $id)
    {
        // (new TimezoneController)->storeTimezoneDefaultJson();

        $timezones = Timezone::where('company_id', $request->company_id)
            ->select('timezone_id', 'json')
            ->get();

        $timezoneIDArray = $timezones->pluck('timezone_id');
        $jsonArray = $timezones->pluck('json')->toArray();

        $TimezoneDefaultJson = TimezoneDefaultJson::query();
        $TimezoneDefaultJson->whereNotIn("index", $timezoneIDArray);
        $defaultArray = $TimezoneDefaultJson->get(["index", "dayTimeList"])->toArray();

        $data = array_merge($defaultArray, $jsonArray);
        //ksort($data);

        asort($data);

        $url = env('SDK_URL') . "/" . "{$id}/WriteTimeGroup";

        $sdkResponse = $this->processSDKRequestBulk($url, $data);

        return $sdkResponse;
    }

    public function renderEmptyTimeFrame()
    {
        $arr = [];

        for ($i = 0; $i <= 6; $i++) {
            $arr[] = [
                "dayWeek" => $i,
                "timeSegmentList" => [
                    [
                        "begin" => "00:00",
                        "end" => "00:00",
                    ],
                    [
                        "begin" => "00:00",
                        "end" => "00:00",
                    ],
                    [
                        "begin" => "00:00",
                        "end" => "00:00",
                    ],
                    [
                        "begin" => "00:00",
                        "end" => "00:00",
                    ],
                    [
                        "begin" => "00:00",
                        "end" => "00:00",
                    ],
                    [
                        "begin" => "00:00",
                        "end" => "00:00",
                    ],
                    [
                        "begin" => "00:00",
                        "end" => "00:00",
                    ],
                    [
                        "begin" => "00:00",
                        "end" => "00:00",
                    ],
                ],
            ];
        }
        return $arr;
    }
    public function PersonAddRangePhotos(Request $request)
    {
        $url = env('SDK_URL') . "/Person/AddRange";

        return $this->processSDKRequestJob($url, $request->all());
    }
    public function PersonAddRange(Request $request)
    {
        $url = env('SDK_URL') . "/Person/AddRange";

        return $this->processSDKRequestBulk($url, $request->all());
    }

    public function PersonAddRangeWithData($data)
    {
        $url = env('SDK_URL') . "/Person/AddRange";

        return $this->processSDKRequestBulk($url, $data);
    }
    public function processSDKRequestJob($url, $data)
    {

        $personList = $data['personList'];
        $snList = $data['snList'];
        $returnFinalMessage = [];
        $devicePersonsArray = [];

        $sdk_url = '';
        if (env("APP_ENV") == "production") {
            $sdk_url = env("SDK_PRODUCTION_COMM_URL");
        } else {
            $sdk_url = env("SDK_STAGING_COMM_URL");
        }

        if ($sdk_url == '') {
            return false;
        }
        $sdk_url = $sdk_url . '/Person/AddRange';
        foreach ($snList as $key => $device) {

            $returnMsg = '';

            foreach ($personList as $keyPerson => $valuePerson) {
                # code...
                $newArray = [
                    "personList" => [$valuePerson],
                    "snList" => [$device],
                ];
                // $newArray[] = $newArray;
                $return = TimezonePhotoUploadJob::dispatch($newArray, $sdk_url);
            }
        }
        $returnFinalMessage = $this->mergeDevicePersonslist($returnFinalMessage);
        $returnContent = [
            "data" => $returnFinalMessage, "status" => 200,
            "message" => "",
            "transactionType" => 0
        ];
        return $returnContent;
    }
    public function mergeDevicePersonslist($data)
    {
        $mergedData = [];

        foreach ($data as $item) {
            $sn = $item['sn'];
            $userList = $item['userList'];

            if (array_key_exists($sn, $mergedData)) {
                if (!empty($userList)) {
                    $mergedData[$sn] = array_merge($mergedData[$sn], $userList);
                }
            } else {
                $mergedData[$sn] = $item;
            }
        }

        $mergedList = [];

        foreach ($mergedData as $sn => $userList) {
            $mergedList[] = [
                "sn" => $sn,
                "state" => $userList['state'],
                "message" => $userList['message'],
                "userList" => $userList['userList'],
            ];
        }
        return $mergedList;
    }
    public function processSDKRequestBulk($url, $data)
    {

        try {
            return Http::timeout(30)->withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $data);
        } catch (\Exception $e) {
            return [
                "status" => 102,
                "message" => $e->getMessage(),
            ];
            // You can log the error or perform any other necessary actions here
        }

        // $data = '{
        //     "personList": [
        //       {
        //         "name": "ARAVIN",
        //         "userCode": 1001,
        //         "faceImage": "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1686213736.jpg"
        //       },
        //       {
        //         "name": "francis",
        //         "userCode": 1006,
        //         "faceImage": "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1686330253.jpg"
        //       },
        //       {
        //         "name": "kumar",
        //         "userCode": 1005,
        //         "faceImage": "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1686330320.jpg"
        //       },
        //       {
        //         "name": "NIJAM",
        //         "userCode": 670,
        //         "faceImage": "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1688228907.jpg"
        //       },
        //       {
        //         "name": "saran",
        //         "userCode": 1002,
        //         "faceImage": "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1686579375.jpg"
        //       },
        //       {
        //         "name": "sowmi",
        //         "userCode": 1003,
        //         "faceImage": "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1686330142.jpg"
        //       },
        //       {
        //         "name": "syed",
        //         "userCode": 1004,
        //         "faceImage": "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1686329973.jpg"
        //       },
        //       {
        //         "name": "venu",
        //         "userCode": 1007,
        //         "faceImage": "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1686578674.jpg"
        //       }
        //     ],
        //     "snList": [
        //       "OX-8862021010076","OX-11111111"
        //     ]
        //   }';
        // $emailJobs = new TimezonePhotoUploadJob();
        // $this->dispatch($emailJobs);

        // $data = json_decode($data, true);
        // $return = TimezonePhotoUploadJob::dispatch($data);
        // // echo exec("php artisan backup:run --only-db");

        // return json_encode($return, true);
    }
    public function getDevicesCountForTimezone(Request $request)
    {
        return Device::where('company_id', $request->company_id)->pluck('device_id');
    }
}
