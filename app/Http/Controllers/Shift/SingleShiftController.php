<?php

namespace App\Http\Controllers\Shift;

use App\Models\Company;
use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Models\AttendanceLog;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class SingleShiftController extends Controller
{

    public $shift_type_id = 6;

    public $result = "";

    public $arr = [];

    public function findAttendanceByUserId($item)
    {
        $model = Attendance::query();
        $model->where("employee_id", $item["employee_id"]);
        $model->where("company_id", $item["company_id"]);
        $model->whereDate("date", $item["date"]);

        return !$model->first() ? false : $model->with(["schedule", "shift"])->first();
    }

    public function processData($companyId, $data, $shift_type_id, $checked = true)
    {
        $items = [];
        $arr = [];
        $ids = [];
        $existing_ids = [];
        $arr["company_id"] = $companyId;
        $arr["date"] = $this->getCurrentDate();

        $str = "";

        foreach ($data as $UserID => $logs) {
            if (count($logs) == 0) {
                $str .= "No log(s) found for Company ID $companyId.\n";
                continue;
            };

            $arr["employee_id"] = $UserID;

            $model = $this->findAttendanceByUserId($arr);

            if (!$model) {
                $arr["shift_type_id"] = $shift_type_id;
                $arr["status"] = "P";
                $arr["device_id_in"] = $logs[0]["DeviceID"];
                $arr["shift_id"] = $logs[0]["schedule"]["shift_id"];
                $arr["roster_id"] = $logs[0]["schedule"]["roster_id"];
                $arr["in"] = $logs[0]["time"];
                $items[] = $arr;
                $ids[] = $logs[0]["id"];

                Attendance::create($arr);
                AttendanceLog::where("id", $logs[0]["id"])->update(["checked" => true]);
            } else {

                $last = array_reverse($logs)[0];
                $arr["out"] = $last["time"];
                $arr["device_id_out"] = $last["DeviceID"];
                $arr["total_hrs"] = $this->getTotalHrsMins($model->in, $last["time"]);
                $schedule = $model->schedule ?? false;
                $isOverTime = $schedule && $schedule->isOverTime ?? false;
                $shift = $last['schedule']['shift'];
                if ($isOverTime) {
                    $arr["ot"] = $this->calculatedOT($arr["total_hrs"], $shift['working_hours'], $shift['overtime_interval']);
                }

                $items[] = $arr;

                $model->update($arr);
                $existing_ids[] = $UserID;
            }
        }
        $new_logs = 0; //$this->storeAttendances($items, $ids);
        $existing_logs = $this->updateAttendances($companyId, $existing_ids);

        $result = $new_logs + $existing_logs;
        $str .= $this->getMeta("SyncSingleShift", "Total $result Log(s) Processed against company $companyId.\n");
        return $str;
    }

    public function storeAttendances($items, $ids)
    {
        Attendance::insert($items);

        return AttendanceLog::whereIn("id", $ids)->update(["checked" => true]);
    }

    public function updateAttendances($companyId, $existing_ids)
    {
        return AttendanceLog::where("UserID", $existing_ids)->where("company_id", $companyId)->update(["checked" => true]);
    }

    public function syncLogsScript()
    {
        $companyIds = Company::pluck("id");

        if (count($companyIds) == 0) {
            return $this->getMeta("SyncSingleShift", "No Company found.");
        }

        return $this->runFunc($this->getCurrentDate(), $companyIds, []);
    }


    public function ClearDB($currentDate, $companyIds, $UserIDs)
    {
        // update attendance_logs table
        DB::table('attendance_logs')
            ->whereDate('LogTime', '=', $currentDate)
            ->whereIn('company_id',  $companyIds)
            ->whereIn('UserID', $UserIDs)
            ->update(['checked' => false]);

        // delete from attendances table
        DB::table('attendances')
            ->whereDate('date', '=', $currentDate)
            ->whereIn('company_id',  $companyIds)
            ->whereIn('employee_id',  $UserIDs)
            ->delete();
    }

    public function processByManual(Request $request)
    {
        $currentDate = $request->input('date', $this->getCurrentDate());
        $companyIds = $request->input('company_ids', []);
        $UserIDs = $request->input('UserIDs', []);
        // $this->ClearDB($currentDate, $companyIds, $UserIDs);
        return $this->runFunc($currentDate, $companyIds, $UserIDs);
    }

    public function processByManualSingle(Request $request)
    {
        $currentDate = $request->input('date', $this->getCurrentDate());
        return $this->runFunc($currentDate, [$request->company_id], [$request->UserID]);
    }

    public function runFunc($currentDate, $companyIds, $UserIDs)
    {
        foreach ($companyIds as $company_id) {
            $data = $this->getModelDataByCompanyId($currentDate, $company_id, $UserIDs, $this->shift_type_id);
            if (count($data) == 0) {
                $this->result .= $this->getMeta("SyncSingleShift", "No Logs found against $company_id Company Id.\n");
                continue;
            }

            $row = $this->processData($company_id, $data, $this->shift_type_id);
            $this->result .= $row;
        }
        return $this->result;
    }
}
