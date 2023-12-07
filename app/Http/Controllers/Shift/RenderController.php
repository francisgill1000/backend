<?php

namespace App\Http\Controllers\Shift;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\EmployeeLeaves;
use App\Models\Holidays;
use App\Models\Reason;
use App\Models\ScheduleEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RenderController extends Controller
{

    public $manual_entry;

    public $reason;

    public $updated_by;

    public function renderMultiInOut(Request $request)
    {
        $shift_type_id = 2;

        $this->reason = $request->reason ?? null;

        $this->updated_by = $request->updated_by ?? 0;

        $company_id = $request->company_id;

        $UserID = $request->UserID;

        $currentDate = $request->date ?? date('Y-m-d');

        $schedule = $this->getScheduleMultiInOut($currentDate, $company_id, $UserID, $shift_type_id);

        if (!$schedule) {
            return $this->response("Employee with $UserID SYSTEM USER ID is not scheduled yet.", null, false);
        }

        $data = $this->getLogs($currentDate, $company_id, $UserID, $shift_type_id);

        if (!count($data)) {
            return $this->response("Employee with $UserID SYSTEM USER ID has no Log(s).", null, false);
        }

        $AttendancePayload = [
            "status" => count($data) % 2 !== 0 ? Attendance::MISSING : Attendance::PRESENT,
            "shift_type_id" => $shift_type_id,
            "date" => $currentDate,
            "company_id" => $company_id,
            "employee_id" => $UserID,
            "shift_id" => $schedule['shift_id'],
            "roster_id" => $schedule['roster_id'],
            "is_manual_entry" => true,
        ];

        $logs = $this->processLogs($data, $schedule);

        return $this->storeOrUpdate($AttendancePayload + $logs);
    }

    public function renderGeneral(Request $request)
    {
        $this->reason = $request->reason ?? null;

        $this->updated_by = $request->updated_by ?? 0;

        $date = $request->date;

        $company_id = $request->company_id;

        $UserID = $request->UserID;

        $schedule = $this->getScheduleGeneral($date, $company_id, $UserID);

        if (!$schedule) {
            return $this->response("Employee with $UserID SYSTEM USER ID is not scheduled yet.", null, false);
        }

        $model = AttendanceLog::query();

        $model->whereDate("LogTime", $date);
        $model->where("company_id", $company_id);
        $model->where("UserID", $UserID);

        $model->whereHas("schedule", function ($q) use ($company_id) {
            $q->whereNot('shift_type_id', 2);
            $q->where("company_id", $company_id);
        });

        $model->distinct("LogTime");

        $count = $model->count();

        $data = [$model->clone()->orderBy("LogTime")->first(), $model->orderBy("LogTime", "desc")->first()];

        if (!$count) {
            return $this->response("Employee with $UserID SYSTEM USER ID has no Log(s).", null, false);
        }

        $arr = [];
        $arr["company_id"] = $company_id;
        $arr["date"] = $date;
        $arr["employee_id"] = $UserID;
        $arr["shift_type_id"] = $schedule["shift_type_id"];
        $arr["shift_id"] = $schedule["shift_id"];
        $arr["roster_id"] = $schedule["roster_id"];
        $arr["device_id_in"] = $data[0]["DeviceID"];
        $arr["in"] = $data[0]["time"];
        $arr["status"] = "M";
        $arr["is_manual_entry"] = true;

        if ($schedule["shift_type_id"] == 4 && $schedule["shift_type_id"] == 6) {

            $LateComing = $this->calculatedLateComing($arr["in"], $schedule["on_duty_time"], $schedule["late_time"]);

            if ($LateComing) {
                // $arr["status"] = "A";
                $arr["late_coming"] = $LateComing;
            }
        }

        if ($count > 1) {
            $arr["status"] = "P";
            $arr["device_id_out"] = $data[1]["DeviceID"];
            $arr["out"] = $data[1]["time"];
            $arr["total_hrs"] = $this->getTotalHrsMins($data[0]["time"], $data[1]["time"]);

            if ($schedule["isOverTime"]) {
                $arr["ot"] = $this->calculatedOT($arr["total_hrs"], $schedule['working_hours'], $schedule['overtime_interval']);
            }

            if ($schedule["shift_type_id"] == 4 && $schedule["shift_type_id"] == 6) {
                $EarlyGoing = $this->calculatedEarlyGoing($arr["in"], $schedule["off_duty_time"], $schedule["early_time"]);

                if ($EarlyGoing) {
                    // $arr["status"] = "A";
                    $arr["early_going"] = $EarlyGoing;
                }
            }
        }

        return $this->storeOrUpdate($arr);
    }

    public function createReason($id)
    {
        if (empty($this->reason)) {
            return false;
        }

        try {
            Reason::create([
                'reason' => $this->reason,
                'user_id' => $this->updated_by,
                'reasonable_id' => $id,
                'reasonable_type' => "App\Models\Attendance",
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getLogs($currentDate, $company_id, $UserID, $shift_type_id)
    {
        $model = AttendanceLog::query();

        // $model->whereHas("schedule", function ($q) use ($shift_type_id, $currentDate) {
        //     $q->where('shift_type_id', $shift_type_id);
        //     $q->whereDate('from_date', "<=", $currentDate);
        //     $q->whereDate('to_date', ">=", $currentDate);
        // });

        $model->where("company_id", $company_id);
        $model->where("UserID", $UserID);
        $model->whereDate("LogTime", $currentDate);

        $model->distinct("LogTime");

        $model->orderBy("LogTime");

        return $model->get(["LogTime", "DeviceID", "UserID", "company_id"])->toArray();
    }

    public function getScheduleMultiInOut($currentDate, $companyId, $UserID, $shift_type_id)
    {
        $schedule = ScheduleEmployee::where('company_id', $companyId)
            ->where("employee_id", $UserID)
            ->where("shift_type_id", $shift_type_id)
            ->first();

        return $this->getSchedule($currentDate, $schedule);
    }

    public function getScheduleGeneral($currentDate, $companyId, $UserID)
    {
        $schedule = ScheduleEmployee::where('company_id', $companyId)
            ->where("employee_id", $UserID)
            ->whereNot("shift_type_id", 2)
            ->first();

        return $this->getSchedule($currentDate, $schedule);
    }

    public function processLogs($data, $schedule)
    {
        $logs = [];
        $total_hrs = 0;
        $ot = 0;
        $totalMinutes = 0;

        for ($i = 0; $i < count($data); $i++) {

            $currentLog = $data[$i];
            $nextLog = isset($data[$i + 1]) ? $data[$i + 1] : false;

            $logs[] = [
                "in" => $currentLog['time'],
                "out" => $nextLog && $nextLog['time'] ? $nextLog['time'] : "---",
            ];

            if ((isset($currentLog['time']) && $currentLog['time'] != '---') and (isset($nextLog['time']) && $nextLog['time'] != '---')) {

                $parsed_out = strtotime($nextLog['time'] ?? 0);
                $parsed_in = strtotime($currentLog['time'] ?? 0);

                if ($parsed_in > $parsed_out) {
                    $parsed_out += 86400;
                }

                $diff = $parsed_out - $parsed_in;

                $minutes = floor($diff / 60);

                $totalMinutes += $minutes > 0 ? $minutes : 0;
            }

            $total_hrs = $this->minutesToHours($totalMinutes);

            if ($schedule['isOverTime']) {
                $ot = $this->calculatedOT($total_hrs, $schedule['working_hours'], $schedule['overtime_interval']);
            }

            $i++;
        }

        return ["logs" => $logs, "total_hrs" => $total_hrs, "ot" => $ot];
    }

    public function storeOrUpdate($items)
    {
        try {

            $attendance = Attendance::firstOrNew([
                'date' => $items['date'],
                'employee_id' => $items['employee_id'],
                'company_id' => $items['company_id'],
            ]);

            $attendance->fill($items)->save();

            if ($this->reason) {
                $result = $this->createReason($attendance->id);
            }

            if (!$attendance) {
                return $this->response("The Logs cannnot render against " . $items['employee_id'] . " SYSTEM USER ID.", null, false);
            }
            return $this->response("The Logs has been render against " . $items['employee_id'] . " SYSTEM USER ID.", null, true);
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function renderOff(Request $request)
    {
        $UserIds = $this->renderOffScript($request->company_id, $request->date, $request->UserID);

        $result = json_encode($UserIds);

        return $this->response("$result Employee has been marked as OFF", null, true);
    }

    public function renderAbsent(Request $request)
    {
        $UserIds = $this->renderAbsentScript($request->company_id, $request->date, $request->UserID);

        $result = json_encode($UserIds);

        if (!count($UserIds)) {
            $result = "No";
        }

        return $this->response("$result Employee has been marked as Absent", null, true);
    }
    public function renderLeaves($company_id = 0, Request $request)
    {
        $schedule = null;
        //if not schedule return nothing
        if ($request->ShiftTypeId == 2) {
            $schedule = $this->getScheduleMultiInOut($request->date, $company_id, $request->UserID, $request->ShiftTypeId);
        } else {
            $schedule = $this->getScheduleGeneral($request->date, $company_id, $request->UserID);
        }

        if (!$schedule) {
            return $this->response("Employee with $request->UserID SYSTEM USER ID is not scheduled yet.", null, false);
        }

        return $this->renderLeavesScript($company_id, $request->date, $request->UserID);
    }
    public function renderHolidays($company_id = 0, Request $request)
    {
        return $this->renderHolidaysScript($company_id, $request->date, $request->UserID);
    }
    public function renderOffCron($company_id = 0)
    {
        $UserIds = $this->renderOffScript($company_id, date("Y-m-d"));

        $result = json_encode($UserIds);

        return $this->getMeta("Sync Off", "$result Employee has been marked as OFF" . ".\n");
    }

    public function renderLeavesCron($company_id = 0)
    {
        $todayDate = date('Y-m-d', strtotime('-1 day'));

        $model = EmployeeLeaves::with(["employee", "leave_type"])
            ->where('company_id', $company_id)
            ->where('status', 1)
            ->where('start_date', '<=', $todayDate)
            ->where('end_date', '>=', $todayDate);

        $employees = $model->get();

        $userIDs = [];
        foreach ($employees as $key => $value) {

            if ($value->employee->system_user_id) { {

                    $userIDs[] = $this->renderLeavesScript($company_id, $todayDate, $value->employee->system_user_id, $value->leave_type->name);
                }
            }
        }
        $result = json_encode($userIDs);

        return $this->getMeta("Sync Leaves", "$result Employee has been marked as Leave" . ".\n");
    }
    public function renderHolidaysCron($company_id = 0)
    {
        $todayDate = date('Y-m-d', strtotime('-1 day'));

        $holidayCount = Holidays::where('company_id', $company_id)
            ->where('start_date', '>=', $todayDate)
            ->where('end_date', '<=', $todayDate)->get()->count();

        if ($holidayCount) {
            $employees = Employee::where('company_id', $company_id)
                // ->where('status', 1)
                ->get();
            $userIDs = [];
            foreach ($employees as $key => $value) {

                if ($value->system_user_id) {
                    $userIDs[] = $this->renderHolidaysScript($company_id, $todayDate, $value->system_user_id);
                }
            }
            $result = json_encode($userIDs);

            return $this->getMeta("Sync Holidays", "$todayDate :  $result Employee has been marked as Holiday" . ".\n");
        }
        return $this->getMeta("Sync Holidays", "$todayDate : No Holiday" . ".\n");
    }

    public function renderOffScript($company_id, $date, $user_id = 0)
    {
        try {
            $model = ScheduleEmployee::query();

            $model->where("company_id", $company_id);

            $model->when($user_id, function ($q) use ($user_id) {
                $q->where("employee_id", $user_id);
            });

            $model->where(function ($q) use ($date) {
                $q->where('from_date', '<=', $date)
                    ->where('to_date', '>=', $date);
            });

            $model->when(!$user_id, function ($q) {
                $q->where("shift_id", -1);
            });

            $employees = $model->distinct("employee_id")->get(["employee_id", "shift_type_id"]);

            $records = [];

            foreach ($employees as $employee) {
                $records[] = [
                    "company_id" => $company_id,
                    "date" => $date,
                    "status" => "O",
                    "employee_id" => $employee->employee_id,
                    "shift_id" => -1,
                    "shift_type_id" => $employee->shift_type_id,
                ];
            }

            $model = Attendance::query();
            // $model->where("shift_id", -1);
            $model->where("company_id", $company_id);
            $model->where("date", $date);
            $model->whereIn("status", ["P", "A", "M", "O"]);

            $model->when($user_id, function ($q) use ($user_id) {
                return $q->where("employee_id", $user_id);
            });

            $model->delete();

            $model->insert($records);

            $UserIds = array_column($records, "employee_id");

            return $UserIds;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function renderAbsentCron($company_id = 0)
    {
        $UserIds = $this->renderAbsentScript($company_id, date('Y-m-d', strtotime('-1 day')));

        $result = json_encode($UserIds);

        if (!count($UserIds)) {
            $result = "No";
        }

        return $this->getMeta("Sync Absent", "$result Employee has been marked as Absent" . ".\n");
    }

    public function renderAbsentScript($company_id, $date, $user_id = 0)
    {
        $model = ScheduleEmployee::query();

        $model->where("company_id", $company_id);

        $model->when($user_id, function ($q) use ($user_id) {
            return $q->where("employee_id", $user_id);
        });

        $model->whereNot("shift_id", -1);

        $model->whereDoesntHave("attendance_logs", function ($q) use ($company_id, $date) {
            $q->whereDate('LogTime', $date);
            $q->where("company_id", $company_id);
        });

        $model->where(function ($q) use ($date) {
            $q->where('from_date', '<=', $date)
                ->where('to_date', '>=', $date);
        });

        $missingEmployees = $model->distinct("employee_id")->get(["employee_id", "shift_type_id"]);

        $records = [];

        foreach ($missingEmployees as $missingEmployee) {
            $records[] = [
                "company_id" => $company_id,
                "date" => $date,
                "status" => "A",
                "employee_id" => $missingEmployee->employee_id,
                "shift_id" => -2,
                "shift_type_id" => $missingEmployee->shift_type_id,
            ];
        }

        $UserIds = array_column($records, "employee_id");

        try {
            if (count($records)) {
                $model = Attendance::query();
                $model->where("company_id", $company_id);
                $model->where("date", $date);
                $model->whereIn("employee_id", $UserIds);
                $model->delete();
                $model->insert($records);
            }

            return $UserIds;
        } catch (\Exception $e) {
            return $e;
        }
    }
    public function renderLeavesScript($company_id, $date, $user_id = 0, $leave_type_name = '')
    {
        try {
            $model = ScheduleEmployee::query();

            $model->where("company_id", $company_id);

            $model->when($user_id, function ($q) use ($user_id) {
                $q->where("employee_id", $user_id);
            });

            $model->when(!$user_id, function ($q) {
                $q->where("shift_id", -3);
            });

            $employees = $model->latest()->first(["employee_id", "shift_type_id"]);

            $records = [];

            // foreach ($employees as $employee)
            {
                if ($leave_type_name != '' && strpos(strtolower($leave_type_name), 'vacation') > -1) {

                    $records[] = [
                        "company_id" => $company_id,
                        "date" => $date,
                        "status" => "V",
                        "employee_id" => $employees->employee_id,
                        "shift_id" => -3,
                        "shift_type_id" => $employees->shift_type_id,
                    ];
                } else {

                    $records[] = [
                        "company_id" => $company_id,
                        "date" => $date,
                        "status" => "L",
                        "employee_id" => $employees->employee_id,
                        "shift_id" => -3,
                        "shift_type_id" => $employees->shift_type_id,
                    ];
                }
            }

            $model = Attendance::query();
            // $model->where("shift_id", -1);
            $model->where("company_id", $company_id);
            $model->where("date", $date);
            $model->whereIn("status", ["P", "A", "M", "O", "L", "H", "V"]);

            $model->when($user_id, function ($q) use ($user_id) {
                return $q->where("employee_id", $user_id);
            });

            $model->delete();

            $model->insert($records);

            $UserIds = array_column($records, "employee_id");

            return $UserIds;
        } catch (\Exception $e) {
            return $e;
        }
    }
    public function renderHolidaysScript($company_id, $date, $user_id = 0)
    {
        try {
            $model = ScheduleEmployee::query();

            $model->where("company_id", $company_id);

            $model->when($user_id, function ($q) use ($user_id) {
                $q->where("employee_id", $user_id);
            });

            $model->when(!$user_id, function ($q) {
                $q->where("shift_id", -4);
            });

            $employees = $model->latest()->first(["employee_id", "shift_type_id"]);

            $records = [];

            // foreach ($employees as $employee)
            {

                $records[] = [
                    "company_id" => $company_id,
                    "date" => $date,
                    "status" => "H",
                    "employee_id" => $employees->employee_id,
                    "shift_id" => -4,
                    "shift_type_id" => $employees->shift_type_id,
                ];
            } {

                $model = Attendance::query();
                // $model->where("shift_id", -1);
                $model->where("company_id", $company_id);
                $model->where("date", $date);
                $model->whereIn("status", ["P", "A", "M", "O", "L", "H", "V"]);

                $model->when($user_id, function ($q) use ($user_id) {
                    return $q->where("employee_id", $user_id);
                });

                $model->delete();

                $model->insert($records);

                $UserIds = $employees->employee_id; // array_column($records, "employee_id");
                if ($UserIds) {
                    return $UserIds;
                }
            }
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function deleteOldRecord($items)
    {
        try {
            $model = Attendance::query();
            $model->whereDate("date", $items['date']);
            $model->where("employee_id", $items['employee_id']);
            $model->where("company_id", $items['company_id'])->delete();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function renderEmployeeReport(Request $request)
    {
        // Define the validation rules
        $rules = [
            'userIds' => 'required|array|max:' . $request->max ?? 10, // Must be an array
            'userIds.*' => 'numeric', // Each value in the array must be numeric
            'date' => 'required|date', // Must be a valid date format
            'company_id' => 'required|numeric', // Must be numeric
        ];

        // Define custom error messages for the 'date' rule
        $customMessages = [
            'date.date' => 'The :attribute field must be a valid date format. E.g. ' . date("Y-m-d"),
        ];

        // Run the validation
        $validator = Validator::make($request->all(), $rules, $customMessages);

        // Check if validation fails    
        if ($validator->fails()) {
            // If validation fails, return the error response
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userIds = $request->userIds ?? [1001, 1006, 1005, 670, 1002, 1003, 1004, 1007];
        $date = $request->date ?? date("Y-m-d");
        $company_id = $request->company_id ?? 8;
        $shift_type_id = $request->shift_type_id ?? 2;
        $statuses = $request->statuses ?? ['A', 'P', 'M', 'O', 'L', 'V', 'H'];



        $response = $this->runEmployeeFunc($userIds, $date, $shift_type_id, $statuses, $company_id);

        return $this->response("Employee Data has been generated", $response, true);
    }

    public function runEmployeeFunc($userIds, $date, $shift_type_id, $statuses, $company_id)
    {
        $arr = [];

        foreach ($userIds as $userId) {
            $in = $this->generateRandomTime('09:30', '14:00');
            $out = $this->generateRandomTime('16:30', '21:30');
            $arr[]  = [
                'date' => $date,
                'employee_id' => $userId,
                'shift_id' => 0,
                'shift_type_id' => $shift_type_id,
                'status' => $statuses[array_rand($statuses)],
                'in' => $in,
                'out' => $out,
                'total_hrs' => $this->calculateTotalHours($in, $out),
                'device_id_in' => "OX-8862021010010",
                'device_id_out' => "OX-8862021010010",
                'date_in' => $date,
                'date_out' => $date,
                'company_id' => $company_id
            ];
        }

        $model = Attendance::query();
        $model->whereIn("employee_id", $userIds)->whereDate("date", $date)->delete();
        $model->insert($arr);

        return $arr;
    }
}
