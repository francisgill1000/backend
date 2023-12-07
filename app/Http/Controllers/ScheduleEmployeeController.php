<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleEmployee\StoreRequest;
use App\Http\Requests\ScheduleEmployee\UpdateRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Roster;
use App\Models\ScheduleEmployee;
use App\Models\ShiftType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ScheduleEmployeeController extends Controller
{

    public function index(Request $request, ScheduleEmployee $model)
    {
        return $model
            ->where('company_id', $request->company_id)
            ->with("shift_type", "shift", "employee")
            ->paginate($request->per_page);
    }

    public function employees_by_departments(Request $request)
    {
        return Employee::select("first_name", "system_user_id", "employee_id", "department_id", "display_name")
            ->withOut(["user", "sub_department", "sub_department", "designation", "role", "schedule"])
            ->whereIn('department_id', $request->department_ids)
            ->where('company_id', $request->company_id)
            ->get();
    }

    public function store(StoreRequest $request, ScheduleEmployee $model)
    {
        $data = $request->validated();

        $arr = [];

        foreach ($data["employee_ids"] as $item) {
            $value = [
                "shift_id" => $data["shift_id"] ?? 0,
                "isOverTime" => $data["isOverTime"],
                "employee_id" => $item,
                "shift_type_id" => $data["shift_type_id"],
                "from_date" => $data["from_date"],
                "to_date" => $data["to_date"],
                "company_id" => $data["company_id"],
            ];
            $found = ScheduleEmployee::where("employee_id", $item)->where("from_date", $data["from_date"])->where("company_id", $data["company_id"])->first();

            if (!$found) {
                $arr[] = $value;
            }
        }

        try {
            $record = $model->insert($arr);

            if ($record) {
                return $this->response('Schedule Employee successfully added.', $record, true);
            } else {
                return $this->response('Schedule Employee cannot add.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function show(ScheduleEmployee $ScheduleEmployee)
    {
        return $ScheduleEmployee;
    }

    public function update(UpdateRequest $request, $id)
    {
        try {
            $record = ScheduleEmployee::where('employee_id', $id)->update($request->validated());
            if ($record) {
                return response()->json(['status' => true, 'message' => 'Schedule Employee successfully updated']);
            } else {
                return response()->json(['status' => false, 'message' => 'Schedule Employee cannot update']);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function destroy($id)
    {
        $record = ScheduleEmployee::where("employee_id", $id)->delete();

        try {
            if ($record) {
                return $this->response('Employee Schedule deleted.', null, true);
            } else {
                return $this->response('Employee Schedule cannot delete.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function deleteSelected(Request $request)
    {
        $record = ScheduleEmployee::whereIn('id', $request->ids)->delete();
        if ($record) {
            return response()->json(['status' => true, 'message' => 'ScheduleEmployee Successfully Deleted']);
        } else {
            return response()->json(['status' => false, 'message' => 'ScheduleEmployee cannot Deleted']);
        }
    }

    public function assignSchedule()
    {
        $companyIds = Company::pluck("id");

        if (count($companyIds) == 0) {
            return "No Record found.";
        }

        $currentDate = date('Y-m-d');

        $currentDay = date("D", strtotime($currentDate));

        $arrays = [];

        $str = "";

        $date = date("Y-m-d H:i:s");
        $script_name = "AssignScheduleToEmployee";

        $meta = "[$date] Cron: $script_name.";

        foreach ($companyIds as $company_id) {

            $no_of_employees = 0;

            $model = ScheduleEmployee::query();

            $model->where("company_id", '>', 0);
            $model->where("company_id", $company_id);

            $model->where(function ($q) use ($currentDate) {
                $q->where('from_date', '<=', $currentDate)
                    ->where('to_date', '>=', $currentDate);
            });

            $model->with(["roster"]);

            $rows = $model->get();

            if ($rows->isEmpty()) {
                $str .= "$meta $no_of_employees employee(s) found for Company ID $company_id.\n";
                continue;
            };

            foreach ($rows as $row) {

                $roster = $row["roster"];

                $index = array_search($currentDay, $roster["days"]);

                $model = ScheduleEmployee::query();
                $model->where("company_id", $company_id);

                $model->where(function ($q) use ($currentDate) {
                    $q->where('from_date', '<=', $currentDate)
                        ->where('to_date', '>=', $currentDate);
                });

                $model->where("employee_id", $row["employee_id"]);
                $model->where("roster_id", $roster["id"]);

                $shiftTypeIdIndex = $roster["shift_type_ids"][$index] == 0 ? $index - 1 : $index;

                $arr = [
                    "shift_id" => $roster["shift_ids"][$index],
                    "shift_type_id" => $roster["shift_type_ids"][$shiftTypeIdIndex],
                ];

                $model->update($arr);
                $arr["employee_id"] = $row["employee_id"];
                $arrays[] = $arr;
                $no_of_employees++;
            }

            $str .= "$meta Total $no_of_employees employee(s) for Company ID $company_id has been scheduled.\n";
        }
        return $str;
    }

    public function assignScheduleByManual(Request $request)
    {
        $company_id = $request->company_id;
        $currentDate = $request->date ?? date('Y-m-d');
        $currentDay = date("D", strtotime($currentDate));

        $employeesScheduled = 0;

        $model = ScheduleEmployee::query();
        $model = $this->custom_with($model, "roster", $company_id);

        $model->where("company_id", $company_id);
        $model->where(function ($q) use ($currentDate) {
            $q->where('from_date', '<=', $currentDate)
                ->where('to_date', '>=', $currentDate);
        });

        $scheduleEmployees = $model->get();

        if ($scheduleEmployees->isEmpty()) {
            return "No employee(s) found";
        }

        foreach ($scheduleEmployees as $schedule) {

            $roster = $schedule->roster;

            $index = array_search($currentDay, $roster->days);

            if ($roster->shift_type_ids[$index] !== 0) {
                $shift_type_id = $roster->shift_type_ids[$index];
            }

            $schedule->update([
                "shift_id" => $roster->shift_ids[$index],
                "shift_type_id" => $roster->shift_type_ids[$index == 0 ? -1 : $index],
                "is_week" => 1,
            ]);

            $employeesScheduled++;
        }

        return "$employeesScheduled Employee(s) has been scheduled.\n";
    }

    public function scheduled_employees(Employee $employee, Request $request)
    {
        return $employee->where("company_id", $request->company_id)
            ->whereHas('schedule', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })->paginate($request->per_page);
    }

    public function not_scheduled_employees(Employee $employee, Request $request)
    {
        return $employee->where("company_id", $request->company_id)
            ->whereDoesntHave('schedule', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })
            ->paginate($request->per_page);
    }

    public function scheduled_employees_index(Request $request)
    {
        $date = $request->date ?? date('Y-m-d');
        $employee = ScheduleEmployee::query();
        $model = $employee->where('company_id', $request->company_id);
        // $model =  $model->whereBetween('from_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        $model->whereDate('from_date', '<=', $date);
        $model->whereDate('to_date', '>=', $date);
        $model->when($request->filled('employee_first_name'), function ($q) use ($request) {

            $q->whereHas('employee', fn (Builder $query) => $query->where('first_name', 'ILIKE', "$request->employee_first_name%"));
        });
        $model->when($request->filled('roster_name'), function ($q) use ($request) {

            $q->whereHas('roster', fn (Builder $query) => $query->where('name', 'ILIKE', "$request->roster_name%"));
        });
        $model->when($request->filled('shift_name'), function ($q) use ($request) {

            $q->whereHas('shift', fn (Builder $query) => $query->where('name', 'ILIKE', "$request->shift_name%"));
        });
        $model->when($request->filled('shift_type_name'), function ($q) use ($request) {

            $q->whereHas('shift_type', fn (Builder $query) => $query->where('name', 'ILIKE', "$request->shift_type_name%"));
        });
        $model->when($request->filled('employee_id'), function ($q) use ($request) {

            //$q->where('employee_id', 'ILIKE', "$request->employee_id%");
            $q->whereHas('employee', fn (Builder $query) => $query->where('employee_id', 'ILIKE', "$request->employee_id%"));
        });
        $model->when($request->filled('show_from_date'), function ($q) use ($request) {

            $q->where('from_date', 'LIKE', "$request->show_from_date%");
        });
        $model->when($request->filled('show_to_date'), function ($q) use ($request) {

            $q->where('to_date', 'LIKE', "$request->show_to_date%");
        });
        $model->when($request->filled('from_date'), function ($q) use ($request) {

            $q->where('from_date', $request->from_date);
        });
        $model->when($request->filled('to_date'), function ($q) use ($request) {

            $q->where('to_date', $request->to_date);
        });
        $model->when($request->filled('isOverTime'), function ($q) use ($request) {

            $q->where('isOverTime', $request->isOverTime);
        });
        $model->when($request->filled('shift_id'), function ($q) use ($request) {

            $q->where('shift_id', $request->shift_id);
        });
        $model->when($request->filled('shift_type_id'), function ($q) use ($request) {

            $q->where('shift_type_id', $request->shift_type_id);
        });

        $model = $this->custom_with($model, "shift", $request->company_id);
        $model = $this->custom_with($model, "roster", $request->company_id);
        $model = $this->custom_with($model, "employee", $request->company_id);

        // $model->when($request->filled('sortBy'), function ($q) use ($request) {
        //     $sortDesc = $request->input('sortDesc');
        //     $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc');
        // });
        $model->when($request->filled('sortBy'), function ($q) use ($request) {
            $sortDesc = $request->input('sortDesc');
            if (strpos($request->sortBy, '.')) {
                if ($request->sortBy == 'employee.first_name') {
                    $q->orderBy(Employee::select("first_name")->where('company_id', $request->company_id)->whereColumn("employees.system_user_id", "schedule_employees.employee_id"), $sortDesc == 'true' ? 'desc' : 'asc');
                } else if ($request->sortBy == 'roster.name') {
                    $q->orderBy(Roster::select("name")->where('company_id', $request->company_id)->whereColumn("rosters.id", "schedule_employees.roster_id"), $sortDesc == 'true' ? 'desc' : 'asc');
                } else if ($request->sortBy == 'shift.name') {
                    $q->orderBy(Roster::select("name")->where('company_id', $request->company_id)->whereColumn("rosters.id", "schedule_employees.roster_id"), $sortDesc == 'true' ? 'desc' : 'asc');
                } else if ($request->sortBy == 'shift_type.name') {
                    $q->orderBy(ShiftType::select("name")->where('company_id', $request->company_id)->whereColumn("shift_types.id", "schedule_employees.shift_type_id"), $sortDesc == 'true' ? 'desc' : 'asc');
                }
            } else {
                $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc');
            }
        });

        return $model->paginate($request->per_page ?? 20);
    }
    public function scheduled_employees_with_type(Employee $employee, Request $request)
    {
        return $employee->where("company_id", $request->company_id)
            ->whereHas('schedule')
            ->withOut(["user", "department", "sub_department", "designation", "role", "schedule"])
            ->when(count($request->department_ids) > 0, function ($q) use ($request) {
                $q->whereIn('department_id', $request->department_ids);
            })
            ->get(["first_name", "system_user_id", "employee_id", "display_name"]);

        return $employee->whereHas('schedule.shift_type', function ($q) use ($request) {
            $q->where('slug', '=', $request->shift_type);
        })->get(["first_name", "system_user_id", "employee_id"]);
    }
}
