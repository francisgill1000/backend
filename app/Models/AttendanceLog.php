<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['show_log_time', "time", "date", "edit_date", "hour_only"];

    protected $casts = [
        // 'LogTime' => 'datetime:d-M-y h:i:s:a',
    ];

    public function getTimeAttribute()
    {
        return date("H:i", strtotime($this->LogTime));
    }

    public function getHourOnlyAttribute()
    {
        return date("H", strtotime($this->LogTime));
    }

    public function getShowLogTimeAttribute()
    {
        return strtotime($this->LogTime);
    }

    public function getDateAttribute()
    {
        return date("d-M-y", strtotime($this->LogTime));
    }

    public function getEditDateAttribute()
    {
        return date("Y-m-d", strtotime($this->LogTime));
    }

    public function device()
    {
        return $this->belongsTo(Device::class, "DeviceID", "device_id")->withDefault(["name" => "---", "device_id" => "---"]);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, "UserID", "system_user_id");
    }

    public function schedule()
    {
        return $this->belongsTo(ScheduleEmployee::class, "UserID", "employee_id")->withOut(["shift_type"]);
    }

    public function reason()
    {
        return $this->morphOne(Reason::class, 'reasonable');
    }
    public function last_reason()
    {
        return $this->hasOne(Reason::class, "id", "reasonable_id")->latest();
    }

    public function filter($request)
    {
        $model = self::query();

        $model->where("company_id", $request->company_id)
            ->with('employee', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })
            ->when($request->filled('department_ids'), function ($q) use ($request) {
                $q->whereHas('employee', fn (Builder $query) => $query->where('department_id', $request->department_ids));
            })

            ->with('device', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })
            // ->when($request->from_date, function ($query) use ($request) {
            //     return $query->whereDate('LogTime', '>=', $request->from_date);
            // })
            // ->when($request->to_date, function ($query) use ($request) {
            //     return $query->whereDate('LogTime', '<=', $request->to_date);
            // })

            ->when($request->filled('dates') && count($request->dates) > 1, function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->where('LogTime', '>=', $request->dates[0])
                        ->where('LogTime', '<=', $request->dates[1]);
                });
            })

            ->when($request->UserID, function ($query) use ($request) {
                return $query->where('UserID', $request->UserID);
            })

            ->when($request->DeviceID, function ($query) use ($request) {
                return $query->where('DeviceID', $request->DeviceID);

                //return $query->where('name', 'like', '%' . $key . '%')->orWhere('email', 'like', '%' . $key . '%');
            })
            ->when($request->filled('department'), function ($q) use ($request) {

                $q->whereHas('employee', fn (Builder $query) => $query->where('department_id', $request->department));
            })
            ->when($request->filled('LogTime'), function ($q) use ($request) {

                $q->where('LogTime', 'LIKE', "$request->LogTime%");
            })
            ->when($request->filled('device'), function ($q) use ($request) {

                $q->where('DeviceID', $request->device);
            })
            ->when($request->filled('devicelocation'), function ($q) use ($request) {
                if ($request->devicelocation != 'All Locations') {

                    $q->whereHas('device', fn (Builder $query) => $query->where('location', 'ILIKE', "$request->devicelocation%"));
                }
            })
            ->when($request->filled('employee_first_name'), function ($q) use ($request) {
                $key = strtolower($request->employee_first_name);
                $q->whereHas('employee', fn (Builder $query) => $query->where('first_name', 'ILIKE', "$key%"));
            })

            ->when($request->filled('sortBy'), function ($q) use ($request) {
                $sortDesc = $request->input('sortDesc');
                if (strpos($request->sortBy, '.')) {
                    if ($request->sortBy == 'employee.first_name') {
                        $q->orderBy(Employee::select("first_name")->where("company_id", $request->company_id)->whereColumn("employees.system_user_id", "attendance_logs.UserID"), $sortDesc == 'true' ? 'desc' : 'asc');
                    } else if ($request->sortBy == 'device.name') {
                        $q->orderBy(Device::select("name")->where("company_id", $request->company_id)->whereColumn("devices.device_id", "attendance_logs.DeviceID"), $sortDesc == 'true' ? 'desc' : 'asc');
                    } else if ($request->sortBy == 'device.location') {
                        $q->orderBy(Device::select("location")->where("company_id", $request->company_id)->whereColumn("devices.device_id", "attendance_logs.DeviceID"), $sortDesc == 'true' ? 'desc' : 'asc');
                    }
                    // } else if ($request->sortBy == 'employee.department') {
                    //     $q->orderBy(Employee::withOut(['schedule', 'department', 'sub_department', 'designation', 'user', 'role'])
                    //             ->join('departments', 'departments.id', '=', 'employees.department_id')
                    //             ->join('attendance_logs', 'attendance_logs.UserID', '=', 'employees.system_user_id')
                    //             ->select('departments.name')
                    //             ->distinct()
                    //             ->where('attendance_logs.company_id', $request->company_id)
                    //             ->when($request->from_date, function ($query) use ($request) {
                    //                 return $query->whereDate('LogTime', '>=', $request->from_date);
                    //             })
                    //             ->when($request->to_date, function ($query) use ($request) {
                    //                 return $query->whereDate('LogTime', '<=', $request->to_date);
                    //             })
                    //         , $sortDesc == 'true' ? 'desc' : 'asc');

                    //
                    //}

                } else {
                    $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc'); {
                    }
                }
            });
        if (!$request->sortBy) {
            $model->orderBy('LogTime', 'DESC');
        }

        return $model;
    }
}
