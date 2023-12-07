<?php

namespace App\Models;

use App\Models\Leave;
use App\Models\Timezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Employee extends Model
{
    use HasFactory;

    // protected $with = [];

    protected $with = ["schedule", "department", "designation", "department", "sub_department"];

    protected $guarded = [];

    protected $casts = [
        'joining_date' => 'date:Y/m/d',
        'created_at' => 'datetime:d-M-y',
    ];

    protected $appends = ['show_joining_date', 'edit_joining_date', 'full_name', 'name', 'name_with_user_id'];

    public function schedule()
    {
        return $this->hasOne(ScheduleEmployee::class, "employee_id", "system_user_id")
            ->where('from_date', '<=', date('Y-m-d'))
            ->where('to_date', '>=', date('Y-m-d'))
            ->orderBy('from_date', 'desc')
            ->withDefault([
                "shift_type_id" => "---",
                "shift_type" => [
                    "name" => "---",
                ],
            ]);
    }

    public function announcements()
    {
        return $this->belongsToMany(Announcement::class)->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            "email" => "---",
        ]);
    }

    public function timezone()
    {
        return $this->belongsTo(Timezone::class, 'timezone_id', 'timezone_id')->withDefault([
            "timezone_name" => "---",
        ]);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class)->withDefault([
            "name" => "---",
        ]);
    }
    public function leave_group()
    {
        return $this->belongsTo(LeaveGroups::class, "leave_group_id", "id");
    }
    public function role()
    {
        return $this->belongsTo(Role::class)->withDefault([
            "name" => "---",
        ]);
    }

    public function payroll()
    {
        return $this->hasOne(Payroll::class);
    }

    public function passport()
    {
        return $this->hasOne(Passport::class)->withDefault([
            "passport_no" => "---",
            "country" => "---",

        ]);
    }

    public function emirate()
    {
        return $this->hasOne(EmiratesInfo::class)->withDefault([
            "emirate_id" => "---",
        ]);
    }

    public function qualification()
    {
        return $this->hasOne(Qualification::class)->withDefault([
            "certificate" => "---",
        ]);
    }

    public function bank()
    {
        return $this->hasOne(BankInfo::class)->withDefault([
            "bank_name" => "---",
            "account_no" => "---",
            "account_title" => "---",
            "address" => "---",
            "iban" => "---",
        ]);
    }

    public function department()
    {
        return $this->belongsTo(Department::class)->withDefault([
            "name" => "---",
        ]);
    }

    public function sub_department()
    {
        return $this->belongsTo(SubDepartment::class)->withDefault([
            "name" => "---",
        ]);
    }

    public function getProfilePictureAttribute($value)
    {
        if (!$value) {
            return null;
        }
        return asset('media/employee/profile_picture/' . $value);
        // return asset(env('BUCKET_URL') . '/' . $value);

    }

    public function getCreatedAtAttribute($value): string
    {
        return date('d M Y', strtotime($value));
    }

    public function getShowJoiningDateAttribute(): string
    {
        return date('d M Y', strtotime($this->joining_date));
    }

    public function getEditJoiningDateAttribute(): string
    {
        return date('Y-m-d', strtotime($this->joining_date));
    }
    public function getNameAttribute(): string
    {
        return $this->first_name ?? "";
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . " " . $this->last_name;
    }

    public function getNameWithUserIDAttribute()
    {
        return $this->display_name . " - " . $this->employee_id;
    }

    // use Illuminate\Database\Eloquent\Builder;

    protected static function boot()
    {
        parent::boot();

        // Order by name ASC
        static::addGlobalScope('order', function (Builder $builder) {
            // $builder->orderBy('id', 'desc');
        });
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, "employee_id", "employee_id");
    }

    public function announcement()
    {
        return $this->belongsToMany(Announcement::class)->withTimestamps();
    }

    /**
     * The roles that belong to the Employee
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function reportTo()
    {
        return $this->belongsToMany(Employee::class, 'employee_report', 'employee_id', 'report_id')->withTimestamps();
    }

    public function leave()
    {
        return $this->hasMany(Leave::class, 'employee_id', 'employee_id');
    }

    public function scopeFilter($query, $search)
    {
        $search = strtolower($search);
        $query->when($search ?? false, fn ($query, $search) =>
        $query->where(
            fn ($query) => $query
                ->where('employee_id', $search)
                ->orWhere(DB::raw('lower(first_name)'), 'Like', '%' . $search . '%')
                ->orWhere(DB::raw('lower(last_name)'), 'Like', '%' . $search . '%')
                ->orWhere(DB::raw('lower(phone_number)'), 'Like', '%' . $search . '%')
                ->orWhere(DB::raw('lower(local_email)'), 'Like', '%' . $search . '%')
                ->orWhere(DB::raw('lower(system_user_id)'), 'Like', '%' . $search . '%')
                ->whereNotNull('first_name')
            // ->orWhere('whatsapp_number', 'Like', '%' . $search . '%')
            // ->orWhere('phone_relative_number', 'Like', '%' . $search . '%')
            // ->orWhere('whatsapp_relative_number', 'Like', '%' . $search . '%')
            // ->orWhereHas(
            //     'user',
            //     fn ($query) =>
            //     $query->Where('email', 'Like', '%' . $search . '%')
            // )
            // ->orWhereHas(
            //     'designation',
            //     fn ($query) =>
            //     $query->Where('name', 'Like', '%' . $search . '%')
            // )
            // ->orWhereHas(
            //     'department',
            //     fn ($query) =>
            //     $query->Where('name', 'Like', '%' . $search . '%')
            // )
        ));
    }

    public function filter($request)
    {
        $model = self::query();

        $model->with([
            "user" => function ($q) {
                return $q->with("role");
            },
        ])
            ->with([
                "reportTo", "department", "sub_department", "designation", "payroll", "timezone", "passport",
                "emirate", "qualification", "bank", "leave_group",
            ])
            ->with(["schedule" => function ($q) {
                $q->with("roster");
            }])
            ->where('company_id', $request->company_id)


            ->when($request->filled('department_ids') && count($request->department_ids) > 0, function ($q) use ($request) {
                $q->whereHas('department', fn (Builder $query) => $query->whereIn('department_id', $request->department_ids));
            })

            ->when($request->filled('department_id'), function ($q) use ($request) {
                $q->whereHas('department', fn (Builder $query) => $query->where('department_id', $request->department_id));
            })
            //filters
            ->when($request->filled('employee_id'), function ($q) use ($request) {
                //$q->where('employee_id', 'LIKE', "$key%");
                $q->where(function ($q) use ($request) {
                    $q->Where('employee_id', 'ILIKE', "$request->employee_id%");
                    $q->orWhere('system_user_id', 'ILIKE', "$request->employee_id%");
                });
            })
            ->when($request->filled('phone_number'), function ($q) use ($request) {

                $q->where('phone_number', 'ILIKE', "$request->phone_number%");
            })
            ->when($request->filled('first_name'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->Where('first_name', 'ILIKE', "$request->first_name%");
                    //$q->orWhere('last_name', 'ILIKE', "$request->first_name%");
                });
            })

            ->when($request->filled('user_email'), function ($q) use ($request) {
                // $q->where('local_email', 'LIKE', "$request->user_email%");
                $q->whereHas('user', fn (Builder $query) => $query->where('email', 'ILIKE', "$request->user_email%"));
            })
            ->when($request->filled('department_name_id'), function ($q) use ($request) {
                // $q->whereHas('department', fn(Builder $query) => $query->where('name', 'ILIKE', "$request->department_name%"));
                $q->whereHas('department', fn (Builder $query) => $query->where('id', $request->department_name_id));
            })

            ->when($request->filled('shceduleshift_id'), function ($q) use ($request) {
                $q->whereHas('schedule', fn (Builder $query) => $query->where('shift_id', $request->shceduleshift_id));
            })
            ->when($request->filled('schedule_shift_name'), function ($q) use ($request) {
                $q->whereHas('schedule.shift', fn (Builder $query) => $query->where('name', 'ILIKE', "$request->schedule_shift_name%"));
                $q->whereHas('schedule.shift', fn (Builder $query) => $query->whereNotNull('name'));
                $q->whereHas('schedule.shift', fn (Builder $query) => $query->where('name', '<>', '---'));
            })
            ->when($request->filled('timezone_name'), function ($q) use ($request) {
                $q->whereHas('timezone', fn (Builder $query) => $query->where('timezone_name', 'ILIKE', "$request->timezone_name%"));
            })
            ->when($request->filled('timezone'), function ($q) use ($request) {
                $q->whereHas('timezone', fn (Builder $query) => $query->where('timezone_id', $request->timezone));
            })

            ->when($request->filled('payroll_basic_salary'), function ($q) use ($request) {
                $q->whereHas('payroll', fn (Builder $query) => $query->where('basic_salary', '=', $request->payroll_basic_salary));
            })
            ->when($request->filled('payroll_net_salary'), function ($q) use ($request) {
                $q->whereHas('payroll', fn (Builder $query) => $query->where('net_salary', '=', $request->payroll_net_salary));
            })

            // ->when($request->filled('sortBy'), function ($q) use ($request) {
            //     $sortDesc = $request->input('sortDesc');
            //     $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc');
            // })

            ->when($request->filled('sortBy'), function ($q) use ($request) {
                $sortDesc = $request->input('sortDesc');
                if (strpos($request->sortBy, '.')) {
                    if ($request->sortBy == 'department.name.id') {
                        $q->orderBy(Department::select("name")->whereColumn("departments.id", "employees.department_id"), $sortDesc == 'true' ? 'desc' : 'asc');
                    } else
                    if ($request->sortBy == 'user.email') {
                        $q->orderBy(User::select("email")->whereColumn("users.id", "employees.user_id"), $sortDesc == 'true' ? 'desc' : 'asc');
                    } else
                    if ($request->sortBy == 'schedule.shift_name') {
                        // $q->orderBy(Schedule::select("shift")->whereColumn("schedule_employees.employee_id", "employees.id"), $sortDesc == 'true' ? 'desc' : 'asc');

                    } else
                    if ($request->sortBy == 'timezone.name') {
                        $q->orderBy(Timezone::select("timezone_name")->whereColumn("timezones.id", "employees.timezone_id"), $sortDesc == 'true' ? 'desc' : 'asc');
                    } else
                    if ($request->sortBy == 'payroll.basic_salary') {
                        $q->orderBy(Payroll::select("basic_salary")->whereColumn("payrolls.employee_id", "employees.id"), $sortDesc == 'true' ? 'desc' : 'asc');
                    } else
                    if ($request->sortBy == 'payroll.net_salary') {
                        $q->orderBy(Payroll::select("net_salary")->whereColumn("payrolls.employee_id", "employees.id"), $sortDesc == 'true' ? 'desc' : 'asc');
                    }
                } else {
                    $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc'); {
                    }
                }
            });

        if (!$request->sortBy) {
            $model->orderBy('first_name', 'asc');
        }

        return $model;
    }
}
