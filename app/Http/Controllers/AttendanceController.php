<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Shift\MultiInOutShiftController;
use App\Http\Controllers\Shift\NightShiftController;
use App\Http\Controllers\Shift\SingleShiftController;
use App\Models\AttendanceLog;
use App\Models\Attendance;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\ScheduleEmployee;
use App\Models\ShiftType;
use Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function ProcessAttendance()
    {

        // $night = new NightShiftController;
        // $night->processNightShift();

        // $single = new SingleShiftController;
        // $single->processSingleShift();

        $multiInOut = new MultiInOutShiftController;
        return $multiInOut->processShift();
    }



    public function SyncAttendance()
    {



        $items = [];
        $model = AttendanceLog::query();
        $model->where("checked", false);
        $model->take(1000);
        if ($model->count() == 0) {
            return false;
        }
        return $logs = $model->get(["id", "UserID", "LogTime", "DeviceID", "company_id"]);

        $i = 0;

        foreach ($logs as $log) {

            $date = date("Y-m-d", strtotime($log->LogTime));

            $AttendanceLog = new AttendanceLog;

            $orderByAsc = $AttendanceLog->where("UserID", $log->UserID)->whereDate("LogTime", $date);
            $orderByDesc = $AttendanceLog->where("UserID", $log->UserID)->whereDate("LogTime", $date);

            $first_log = $orderByAsc->orderBy("LogTime")->first() ?? false;
            $last_log =  $orderByDesc->orderByDesc('LogTime')->first() ?? false;

            $logs = $AttendanceLog->where("UserID", $log->UserID)->whereDate("LogTime", $date)->count();

            $item = [];
            $item["company_id"] = $log->company_id;
            $item["employee_id"] = $log->UserID;
            $item["date"] = $date;

            if ($first_log) {
                $item["in"] = $first_log->time;
                $item["status"] = "---";
                $item["device_id_in"] = $first_log->DeviceID ?? "---";
            }
            if ($logs > 1 && $last_log) {
                $item["out"] = $last_log->time;
                $item["device_id_out"] = $last_log->DeviceID ?? "---";
                $item["status"] = "P";
                $diff = abs(($last_log->show_log_time - $first_log->show_log_time));
                $h = floor($diff / 3600);
                $m = floor(($diff % 3600) / 60);
                $item["total_hrs"] = (($h < 10 ? "0" . $h : $h) . ":" . ($m < 10 ? "0" . $m : $m));
            }

            $attendance = Attendance::whereDate("date", $date)->where("employee_id", $log->UserID);

            $attendance->first() ? $attendance->update($item) : Attendance::create($item);

            AttendanceLog::where("id", $log->id)->update(["checked" => true]);

            $i++;

            // $items[$date][$log->UserID] = $item;
        }

        return $i;
    }

    public function SyncAbsent()
    {
        $previousDate = date('Y-m-d', strtotime('-1 days'));

        $employeesThatDoesNotExist = ScheduleEmployee::with('roster')->whereDoesntHave('attendances', function ($q) use ($previousDate) {
            $q->whereDate('date', $previousDate);
        })
            ->get(["employee_id", "company_id", "roster_id"])
            ->groupBy("company_id");

        // Debug
        // $employeesThatDoesNotExist = ScheduleEmployee::whereIn("company_id", [1, 8])->whereIn("employee_id", [1001])
        //     ->whereDoesntHave('attendances', function ($q) use ($previousDate) {
        //         $q->whereDate('date', $previousDate);
        //     })
        //     ->get(["employee_id", "company_id"]);

        return $this->runFunc($employeesThatDoesNotExist, $previousDate);
    }


    public function SyncAbsentByManual(Request $request)
    {
        // return $this->SyncAbsent();

        $date = $request->input('date', date('Y-m-d'));
        $previousDate = date('Y-m-d', strtotime($date . '-1 days'));
        // return [$date, $previousDate];
        $model = ScheduleEmployee::whereIn("company_id", $request->company_ids);

        $model->when(count($request->UserIDs ?? []) > 0, function ($q) use ($request) {
            $q->whereIn("employee_id", $request->UserIDs);
        });

        $model->whereDoesntHave('attendances', function ($q) use ($previousDate) {
            $q->whereDate('date', $previousDate);
        });

        return $employeesThatDoesNotExist =  $model->with('roster')
            ->get(["employee_id", "company_id", "shift_type_id", "roster_id"])
            ->groupBy("company_id");
        return $this->runFunc($employeesThatDoesNotExist, $previousDate);
    }


    public function SyncAbsentForMultipleDays()
    {
        $first = AttendanceLog::orderBy("id")->first();
        $today = date('Y-m-d');
        $startDate = $first->edit_date;
        $difference = strtotime($startDate) - strtotime($today);
        $days = abs($difference / (60 * 60) / 24);
        $arr = [];

        for ($i = $days; $i > 0; $i--) {
            $arr[] = $this->SyncAbsent($i);
        }

        return json_encode($arr);
    }

    public function ResetAttendance(Request $request)
    {
        $items = [];
        $model = AttendanceLog::query();
        $model->whereBetween("LogTime", [$request->from_date ?? date("Y-m-d"), $request->to_date ?? date("Y-m-d")]);
        $model->where("DeviceID", $request->DeviceID);

        if ($model->count() == 0) {
            return false;
        }
        $logs = $model->get(["id", "UserID", "LogTime", "DeviceID", "company_id"]);


        $i = 0;

        foreach ($logs as $log) {

            $date = date("Y-m-d", strtotime($log->LogTime));

            $AttendanceLog = new AttendanceLog;

            $orderByAsc = $AttendanceLog->where("UserID", $log->UserID)->whereDate("LogTime", $date);
            $orderByDesc = $AttendanceLog->where("UserID", $log->UserID)->whereDate("LogTime", $date);

            $first_log = $orderByAsc->orderBy("LogTime")->first() ?? false;
            $last_log =  $orderByDesc->orderByDesc('LogTime')->first() ?? false;

            $logs = $AttendanceLog->where("UserID", $log->UserID)->whereDate("LogTime", $date)->count();

            $item = [];
            $item["company_id"] = $log->company_id;
            $item["employee_id"] = $log->UserID;
            $item["date"] = $date;

            if ($first_log) {
                $item["in"] = $first_log->time;
                $item["status"] = "---";
                $item["device_id_in"] = Device::where("device_id", $first_log->DeviceID)->pluck("id")[0] ?? "---";
            }
            if ($logs > 1 && $last_log) {
                $item["out"] = $last_log->time;
                $item["device_id_out"] = Device::where("device_id", $last_log->DeviceID)->pluck("id")[0] ?? "---";
                $item["status"] = "P";
                $diff = abs(($last_log->show_log_time - $first_log->show_log_time));
                $h = floor($diff / 3600);
                $m = floor(($diff % 3600) / 60);
                $item["total_hrs"] = (($h < 10 ? "0" . $h : $h) . ":" . ($m < 10 ? "0" . $m : $m));
            }


            $attendance = Attendance::whereDate("date", $date)->where("employee_id", $log->UserID);

            $attendance->first() ? $attendance->update($item) : Attendance::create($item);

            AttendanceLog::where("id", $log->id)->update(["checked" => true]);

            $i++;

            $items[$date][$log->UserID] = $item;
        }

        Storage::disk('local')->put($request->DeviceID . '-' . date("d-M-y") . '-reset_attendance.txt', json_encode($items));

        return $i;
    }

    public function runFunc($companyIDs, $previousDate)
    {
        $result = null;
        $record = [];
        foreach ($companyIDs as $companyID => $employeesThatDoesNotExist) {
            $NumberOfEmployee = count($employeesThatDoesNotExist);

            if (!$NumberOfEmployee) {
                $result .= $this->getMeta("SyncAbsent", "No employee(s) found against company id $companyID .\n");
                continue;
            }

           
            $employee_ids = [];
            foreach ($employeesThatDoesNotExist as $employee) {
                $arr = [
                    "employee_id"   => $employee->employee_id,
                    "date"          => $previousDate,
                    "status"        => $this->getDynamicStatus($employee, $previousDate),
                    "company_id"    => $employee->company_id,
                    "shift_type_id"    => $employee->shift_type_id,
                    "created_at"    => now(),
                    "updated_at"    => now()
                ];
                $record[] = $arr;

                $employee_ids[] = $employee->employee_id;
            }

            $result .= $this->getMeta("SyncAbsent", "$NumberOfEmployee employee(s) absent against company id $companyID.\n Employee IDs: " . json_encode($employee_ids));
        }


        Attendance::insert($record);
        // return $record[0];
        return $result;
    }

    public function getDynamicStatus($employee, $date)
    {
        $shift = array_filter($employee->roster->json, function ($shift) use ($date) {
            return $shift['day'] ==  date('D', strtotime($date));
        });

        $obj = reset($shift);

        if ($obj['shift_id'] == -1) {
            return "OFF";
        }
        return "A";
    }
}
