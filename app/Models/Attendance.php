<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    const ABSENT = "A"; //1;
    const PRESENT = "P"; //2;
    const MISSING = "M"; //3;

    protected $guarded = [];

    protected $appends = [
        "edit_date",
        "day",
    ];

    protected $casts = [
        'date' => 'date',
        'logs' => 'array',
        'shift_type_id' => 'integer',
    ];

    protected $hidden = ["branch_id", "created_at", "updated_at"];
    // protected $hidden = ["company_id", "branch_id", "created_at", "updated_at"];

    public function shift()
    {
        return $this->belongsTo(Shift::class)->withOut("shift_type");
    }

    public function shift_type()
    {
        return $this->belongsTo(ShiftType::class);
    }

    public function getDateAttribute($value)
    {
        return date("d-M-y", strtotime($value));
    }

    public function getDayAttribute()
    {
        return date("D", strtotime($this->date));
    }
    public function getHrsMins($difference)
    {
        $h = floor($difference / 3600);
        $h = $h < 0 ? "0" : $h;
        $m = floor($difference % 3600) / 60;
        $m = $m < 0 ? "0" : $m;

        return (($h < 10 ? "0" . $h : $h) . ":" . ($m < 10 ? "0" . $m : $m));
    }

    // public function getTotalHrsAttribute($value)
    // {
    //     return strtotime($value) < strtotime('18:00') ? $value : '00:00';
    // }

    /**
     * Get the user that owns the Attendance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function device_in()
    {
        return $this->belongsTo(Device::class, 'device_id_in', 'device_id')->withDefault([
            'name' => '---',
        ]);
    }

    /**
     * Get the user that owns the Attendance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function device_out()
    {
        return $this->belongsTo(Device::class, 'device_id_out', 'device_id')->withDefault([
            'name' => '---',
        ]);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, "employee_id", "system_user_id")->withOut("schedule")->withDefault([
            'first_name' => '---',
            "department" => [
                "name" => "---",
            ],
        ]);
    }

    public function employeeAttendance()
    {
        return $this->belongsTo(Employee::class, "employee_id");
    }

    public function getEditDateAttribute()
    {
        return date("Y-m-d", strtotime($this->date));
    }

    public function AttendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, "UserID", "employee_id");
    }

    public function schedule()
    {
        return $this->belongsTo(ScheduleEmployee::class, "employee_id", "employee_id")->withOut(["shift_type"]);
    }

    public function roster()
    {
        return $this->belongsTo(Roster::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('order', function (Builder $builder) {
            //$builder->orderBy('id', 'desc');
        });
    }

    public function last_reason()
    {
        return $this->hasOne(Reason::class, 'reasonable_id', 'id')->latest();
    }

    public function processAttendanceModel($request)
    {
        $model = self::query();

        $model->where('company_id', $request->company_id);
        $model->with(['shift_type', 'last_reason']);


        $model->when($request->main_shift_type && $request->main_shift_type == 2, function ($q) {
            $q->where('shift_type_id', 2);
        });

        $model->when($request->main_shift_type && $request->main_shift_type != 2, function ($q) {
            $q->whereNot('shift_type_id', 2);
        });

        $department_ids = $request->department_ids;

        if (gettype($department_ids) !== "array") {
            $department_ids = explode(",", $department_ids);
        }

        $model->when($request->filled('department_id') && count($department_ids) > 0, function ($q) use ($request, $department_ids) {
            $q->whereIn('employee_id', Employee::whereIn("department_id", $department_ids)->where('company_id', $request->company_id)->pluck("system_user_id"));
        });

        $model->when($request->status == "A", function ($q) {
            $q->where('status', "A");
        });
        $model->when($request->status == "P", function ($q) {
            $q->where('status', "P");
        });
        $model->when($request->status == "M", function ($q) {
            $q->where('status', "M");
        });
        $model->when($request->status == "O", function ($q) {
            $q->where('status', "O");
        });
        $model->when($request->status == "L", function ($q) {
            $q->where('status', "L");
        });
        $model->when($request->status == "V", function ($q) {
            $q->where('status', "V");
        });
        $model->when($request->status == "H", function ($q) {
            $q->where('status', "H");
        });

        $model->when($request->status == "V", function ($q) {
            $q->where('status', "V");
        });

        $model->when($request->status == "ME", function ($q) {
            $q->where('is_manual_entry', true);
        });

        $model->when($request->late_early == "LC", function ($q) {
            $q->where('late_coming', "!=", "---");
        });

        $model->when($request->late_early == "EG", function ($q) {
            $q->where('early_going', "!=", "---");
        });

        $model->when($request->overtime == 1, function ($q) {
            $q->where('ot', "!=", "---");
        });

        $model->when($request->daily_date && $request->report_type == 'Daily', function ($q) use ($request) {
            $q->whereDate('date', $request->daily_date);
            //$q->orderBy("id", "desc");
        });

        $model->when($request->from_date && $request->to_date && $request->report_type != 'Daily', function ($q) use ($request) {
            $q->whereBetween("date", [$request->from_date, $request->to_date]);
            // $q->orderBy("date", "asc");
        });

        $model->when($request->start_date && $request->end_date && $request->report_type != 'Daily', function ($q) use ($request) {
            $q->whereBetween("date", [$request->start_date, $request->end_date]);
            // $q->orderBy("date", "asc");
        });



        $model->with('employee', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
            $q->with('department');
        });

        $model->with('device_in', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });

        $model->with('device_out', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });

        $model->with('shift', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
        });

        $model->with('schedule');

        $model->when($request->filled('date'), function ($q) use ($request) {
            $q->whereDate('date', '=', $request->date);
        });
        $model->when($request->filled('employee_id'), function ($q) use ($request) {
            $q->where('employee_id', 'LIKE', "$request->employee_id%");
        });

        $model->when($request->filled('employee_first_name') && $request->employee_first_name != '', function ($q) use ($request) {
            // $key = strtolower($request->employee_first_name);
            $q->whereHas('employee', fn (Builder $q) => $q->where('first_name', 'ILIKE', "$request->employee_first_name%"));
        });
        $model->when($request->filled('employee_department_name'), function ($q) use ($request) {
            // $key = strtolower($request->employee_department_name);
            $q->whereHas('employee.department', fn (Builder $query) => $query->where('company_id', $request->company_id)->where('name', 'ILIKE', "$request->employee_department_name%"));
        });
        if ($request->shift) {
            //$key = strtolower($request->shift_type_name);
            $model->where(function ($q) use ($request) {
                return $q->whereIn("shift_type_id", ShiftType::where('name', 'ILIKE', "$request->shift%")->pluck("id"));
            });
        }
        $model->when($request->filled('in'), function ($q) use ($request) {
            // $key = strtolower($request->in);
            $q->where('in', 'LIKE', "$request->in%");
        });
        $model->when($request->filled('out'), function ($q) use ($request) {
            // $key = strtolower($request->out);
            $q->where('out', 'LIKE', "$request->out%");
        });
        $model->when($request->filled('total_hrs'), function ($q) use ($request) {
            //$key = strtolower($request->total_hrs);
            $q->where('total_hrs', 'LIKE', "$request->total_hrs%");
        });
        $model->when($request->filled('ot'), function ($q) use ($request) {
            //$key = strtolower($request->ot);
            $q->where('ot', 'LIKE', "$request->ot%");
        });

        $model->when($request->filled('sortBy'), function ($q) use ($request) {
            $sortDesc = $request->input('sortDesc');

            $q->orderBy($request->sortBy, $sortDesc == 'true' ? 'desc' : 'asc');
        });
        $model->when(!$request->filled('sortBy'), function ($q) use ($request) {
            $q->orderBy('date', 'asc');
        });
        return $model;
    }
}
