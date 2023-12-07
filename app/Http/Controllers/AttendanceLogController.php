<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Log as Logger;
use Illuminate\Support\Facades\Storage;

class AttendanceLogController extends Controller
{
    public function index(AttendanceLog $model, Request $request)
    {
        return $model->filter($request)->count();

        return $model->filter($request)->paginate($request->per_page);
    }
    public function getAttendanceLogs(AttendanceLog $model, Request $request)
    {
        return $model->where("company_id", $request->company_id)->paginate($request->per_page);
    }

    public function checkMissingLogsCount()
    {
        $fullPath = storage_path("app/logs-" . date("d-m-Y") . ".csv");

        $file = fopen($fullPath, 'r');
        $data = file($fullPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        fclose($file);

        $actualCount = (int) Storage::get("logs-count-" . date("d-m-Y") . ".txt");
        $expectedCount = count($data);

        // if ($expectedCount === $actualCount) {
        //     throw new \Exception("Log count mismatch. Expected: $expectedCount, Actual: $actualCount");
        // }

        return $this->getMeta("Check Missing Count", "Final log count $expectedCount. Last inserted log count: $actualCount.\n");
    }

    public function handleFile()
    {
        $date = date("d-m-Y");

        $csvPath = "app/logs-$date.csv"; // The path to the file relative to the "Storage" folder

        $fullPath = storage_path($csvPath);

        if (!file_exists($fullPath)) {
            return ["error" => true, "message" => 'File doest not exist.'];
        }

        $file = fopen($fullPath, 'r');

        $data = file($fullPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (!count($data)) {
            return ["error" => true, "message" => 'File is empty.'];
        }

        $previoulyAddedLineNumbers = Storage::get("logs-count-$date.txt") ?? 0;

        // return $this->getMeta("Sync Attenance Logs", $previoulyAddedLineNumbers . "\n");

        $totalLines = count($data);

        $currentLength = 0;

        if ($previoulyAddedLineNumbers == $totalLines) {
            return ["error" => true, "message" => 'No new data found.'];
        } else if ($previoulyAddedLineNumbers > 0 && $totalLines > 0) {
            $currentLength = $previoulyAddedLineNumbers;
        }

        fclose($file);

        return [
            "date" => $date,
            "totalLines" => $totalLines,
            "data" => array_slice($data, $currentLength)

        ];
    }

    public function store()
    {
        $result = $this->handleFile();

        if (array_key_exists("error", $result)) {
            return $this->getMeta("Sync Attenance Logs", $result["message"] . "\n");
        }

        $records = [];

        foreach ($result["data"] as $row) {
            $columns = explode(',', $row);

            $records[] = [
                "UserID" => $columns[0],
                "DeviceID" => $columns[1],
                "LogTime" => substr(str_replace("T", " ", $columns[2]), 0, -3),
                "SerialNumber" => $columns[3]
            ];
        }

        try {
            AttendanceLog::insert($records);
            Logger::channel("custom")->info(count($records) . ' new logs has been inserted.');
            Storage::put("logs-count-" . $result['date'] . ".txt", $result['totalLines']);
            return $this->getMeta("Sync Attenance Logs", count($records) . " new logs has been inserted." . "\n");
        } catch (\Throwable $th) {

            Logger::channel("custom")->error('Error occured while inserting logs.');
            Logger::channel("custom")->error('Error Details: ' . $th);
            return $this->getMeta("Sync Attenance Logs", " Error occured." . "\n");

            // return $data = [
            //     'title' => 'Quick action required',
            //     'body' => $th,
            // ];
            // Mail::to(env("ADMIN_MAIL_RECEIVERS"))->send(new NotifyIfLogsDoesNotGenerate($data));
        }
    }

    public function singleView(AttendanceLog $model, Request $request)
    {
        return $model->where('UserID', $request->UserID)
            ->where('company_id', $request->company_id)
            ->whereDate('LogTime', $request->LogTime)
            ->select("LogTime")
            ->distinct("LogTime")
            ->orderBy('LogTime')
            ->paginate($request->per_page ?? 100);
    }

    public function GenerateManualLog(Request $request)
    {
        try {
            AttendanceLog::create($request->only(['UserID', 'LogTime', 'DeviceID', 'company_id']));

            return [
                'status' => true,
                'message' => 'Log Successfully Updated',
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function GenerateLog(Request $request)
    {
        try {
            $log = AttendanceLog::create($request->all());

            if ($log) {
                // $Attendance = new AttendanceController;
                // $Attendance->SyncAttendance();
                return [
                    'status' => true,
                    'message' => 'Log Successfully Updated',
                ];
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function SyncCompanyIdsWithDevices()
    {

        // return 282+499+245+257+335+209;
        // get device ids with company ids = 0
        $model = AttendanceLog::query();
        $model->distinct('DeviceID');
        $model->where("company_id", 0);
        $model->take(10);
        $free_device_ids = $model->pluck("DeviceID");

        // get company ids against found device ids
        $rows = Device::whereIn("device_id", $free_device_ids)->get(["company_id", "device_id as DeviceID"])->toArray();

        if (count($rows) == 0 || count($free_device_ids) == 0) {
            Logger::channel("custom")->info('No new record found while updating company ids for device');
            return 'No new record found while updating company ids for device';
        }

        foreach ($rows as $arr) {
            try {
                AttendanceLog::where("company_id", 0)->where("DeviceID", $arr["DeviceID"])->update($arr);
            } catch (\Throwable $th) {
                Logger::channel("custom")->error('Error occured while updating company ids.');
                Logger::channel("custom")->error('Error Details: ' . $th);
                $th;
            }
        }
        Logger::channel("custom")->info("Company IDS has been updated. Details: " . json_encode($rows));

        return "Company IDS has been updated. Details: " . json_encode($rows);
    }

    public function getShift($log)
    {
        return $log;
    }
    public function AttendanceLogsDaily(Request $request, $id)
    {
        $model = Employee::query();

        $model->with(["first_log.device", "last_log.device"]);

        return $model->paginate($request->per_page);
    }

    public function AttendanceLogsMonthly(Request $request, $id)
    {
        $model = AttendanceLog::query();
        $model->whereMonth("LogTime", date("m"))->orderBy("LogTime");
        $array = $model->get()->groupBy(["date", "UserID"])->toArray();

        $arr = [];

        foreach ($array as $value) {
            foreach ($value as $av) {
                $arr[]["logs"] = [
                    "first" => $av[0],
                    "last" => $av[count($av) - 1],
                ];
            }
        }

        return $arr;
    }

    public function AttendanceLogsSearch(AttendanceLog $model, Request $request, $id, $key)
    {
        if ($request->type) {
            $model = $model->getFilteredByTimeStamp($request->type);
        }

        if ($key) {
            $model = $this->searchWithRelation($model, $key);
        }
        return $this->getLogs($model, $request, $id);
    }

    public function searchWithRelation($model, $key)
    {
        $model->WhereHas("employee", function ($q) use ($key) {
            $q->where('employee_id', 'LIKE', "%$key%");
            $q->orWhere('first_name', 'LIKE', "%$key%");
            $q->orWhere('last_name', 'LIKE', "%$key%");
        });

        return $model;
    }

    public function getFilteredByTimeStamp($model)
    {
        $model->whereHas("log_monthly", function ($q) {
            $q->whereMonth("LogTime", date("m"));
        });

        return $model->get()->toArray();
    }

    public function AttendanceLogPaginate(AttendanceLog $model, Request $request)
    {

        // return  $model = $model->count();
        $array = $model->limit($request->input("per_page"))->get()->toArray();
        $total = count($array);
        $current_page = $request->input("page") ?? 1;

        $starting_point = ($current_page * $request->input("per_page")) - $request->input("per_page");

        $array = array_slice($array, $starting_point, $request->input("per_page"), true);

        $array = new Paginator($array, $total, $request->input("per_page"), $current_page, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return $array;
    }

    public function findClosest($arr, $n, $target)
    {
        // Corner cases
        if ($target <= $arr[0]->time_in_numbers) {
            return $arr[0];
        }

        if ($target >= $arr[$n - 1]->time_in_numbers) {
            return $arr[$n - 1];
        }

        // Doing binary search
        $i = 0;
        $j = $n;
        $mid = 0;
        while ($i < $j) {
            $mid = ($i + $j) / 2;

            if ($arr[$mid]->time_in_numbers == $target) {
                return $arr[$mid];
            }

            /* If target is less than array element,
            then search in left */
            if ($target < $arr[$mid]->time_in_numbers) {

                // If target is greater than previous
                // to mid, return closest of two
                if ($mid > 0 && $target > $arr[$mid - 1]->time_in_numbers) {
                    return $this->getClosest($arr[$mid - 1], $arr[$mid], $target);
                }

                /* Repeat for left half */
                $j = $mid;
            }

            // If target is greater than mid
            else {
                if ($mid < $n - 1 && $target < $arr[$mid + 1]->time_in_numbers) {
                    return $this->getClosest($arr[$mid], $arr[$mid + 1], $target);
                }

                // update i
                $i = $mid + 1;
            }
        }

        // Only single element left after search
        return $arr[$mid];
    }
    public function getClosest($val1, $val2, $target)
    {
        return ($target - $val1->time_in_numbers > $val2->time_in_numbers - $target) ? $val2 : $val1;
    }

    public function Search(Request $request, $company_id)
    {

        $model = AttendanceLog::query();

        $model->where("company_id", $request->company_id);

        // $model->whereDate('LogTime', '>=', $request->from_date);
        // $model->whereDate('LogTime', '<=', $request->to_date);

        $model->when($request->from_date, function ($query) use ($request) {
            return $query->whereDate('LogTime', '>=', $request->from_date . ' 00:00:00');
        });

        $model->when($request->to_date, function ($query) use ($request) {
            return $query->whereDate('LogTime', '<=', $request->to_date . ' 23:59:59');
        });

        $model->when($request->UserID, function ($query) use ($request) {
            return $query->where('UserID', $request->UserID);
        });

        $model->when($request->DeviceID, function ($query) use ($request) {
            return $query->where('DeviceID', $request->DeviceID);
        });

        $model->when($request->filled('search_system_user_id'), function ($q) use ($request) {
            $q->where('UserID', 'LIKE', "$request->search_system_user_id%");
        });
        $model->when($request->filled('search_time'), function ($q) use ($request) {
            $q->where('LogTime', 'LIKE', "$request->search_time%");
        });
        $model->when($request->filled('search_device_name'), function ($q) use ($request) {
            $key = strtolower($request->search_device_name);
            $q->whereHas('device', fn (Builder $query) => $query->where('name', 'ILIKE', "$key%"));
        });
        $model->when($request->filled('search_device_id'), function ($q) use ($request) {
            $q->where('DeviceID', 'LIKE', "$request->search_device_id%");
        });

        return $model
            ->with("device")
            ->orderBy('LogTime', 'desc')
            ->paginate($request->per_page ?? 100);
    }
}
