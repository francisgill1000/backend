<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeLeaves\StoreRequest;
use App\Http\Requests\EmployeeLeaves\UpdateRequest;
use App\Models\Employee;
use App\Models\EmployeeLeaves;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeLeavesController extends Controller
{
    public function getDefaultModelSettings($request)
    {
        $model = EmployeeLeaves::query();
        $model->with(["leave_type", "employee.leave_group", "reporting"]);
        $model->where('company_id', $request->company_id);
        // $model->where('year', $request->year);
        $model->when($request->filled('employee_id'), function ($q) use ($request) {
            $q->where('employee_id', $request->employee_id);
        });
        $model->when($request->filled('employee_name'), function ($q) use ($request) {
            $q->whereHas('employee', fn(Builder $query) => $query->where('first_name', 'ILIKE', "$request->employee_name%"));
        });
        // $model->when($request->filled('group_name'), function ($q) use ($request) {
        //     $q->whereHas('employee.leave_group', fn(Builder $query) => $query->where('group_name', 'ILIKE', "$request->group_name%"));
        // });
        $model->when($request->filled('group_name_id'), function ($q) use ($request) {
            $q->whereHas('employee', fn(Builder $query) => $query->where('leave_group_id', $request->group_name_id));
        });
        $model->when($request->filled('leave_type_id'), function ($q) use ($request) {
            $q->where('leave_type_id', $request->leave_type_id);
        });
        $model->when($request->filled('start_date'), function ($q) use ($request) {
            $q->where('start_date', 'ILIKE', "$request->start_date%");
        });
        $model->when($request->filled('end_date'), function ($q) use ($request) {
            $q->where('end_date', 'ILIKE', "$request->end_date%");
        });
        $model->when($request->filled('leave_note'), function ($q) use ($request) {
            $q->where('reason', 'ILIKE', "$request->leave_note%");
        });
        $model->when($request->filled('reporting'), function ($q) use ($request) {
            $q->whereHas('reporting', fn(Builder $query) => $query->where('first_name', 'ILIKE', "$request->reporting%"));
        });
        $model->when($request->filled('created_at'), function ($q) use ($request) {
            $q->where('created_at', 'ILIKE', "$request->created_at%");
        });
        $model->when($request->filled('status'), function ($q) use ($request) {
            if (strtolower($request->status) == 'approved') {
                $q->where('status', 1);
            } else if (strtolower($request->status) == 'rejected') {
                $q->where('status', 2);
            } else if (strtolower($request->status) == 'pending') {
                $q->where('status', 0);
            }

        });
        $model->when($request->filled('sortBy'), function ($q) use ($request) {
            $sortDesc = $request->input('sortDesc');
            if (strpos($request->sortBy, '.')) {
                if ($request->sortBy == 'employee.name') {
                    $q->orderBy(Employee::select("first_name")->whereColumn("employees.id", "employee_leaves.employee_id"), $sortDesc == 'true' ? 'desc' : 'asc');

                } else if ($request->sortBy == 'group.name') {
                    $q->orderBy(Employee::select("first_name")->whereColumn("employees.id", "employee_leaves.employee_id"), $sortDesc == 'true' ? 'desc' : 'asc');

                } else if ($request->sortBy == 'leave_type.name') {
                    $q->orderBy(LeaveType::select("name")->whereColumn("leave_types.id", "employee_leaves.leave_type_id"), $sortDesc == 'true' ? 'desc' : 'asc');

                }

            } else {
                $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc');{}

            }

        });

        if (!$request->sortBy) {
            $model->orderBy('id', 'desc');
        }
        return $model;
    }

    public function index(Request $request)
    {

        return $this->getDefaultModelSettings($request)->paginate($request->per_page ?? 100);
    }

    function list(Request $request) {
        return $this->getDefaultModelSettings($request)->paginate($request->per_page ?? 100);
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();

        try {
            // Database operations
            $record = EmployeeLeaves::create($request->all());

            DB::commit();
            if ($record) {

                return $this->response('Employee Leave Successfully created.', $record, true);
            } else {
                return $this->response('Employee Leave cannot be created.', null, false);
            }
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }
    public function update(UpdateRequest $request, EmployeeLeaves $EmployeeLeaves, $id)
    {

        try {
            $record = $EmployeeLeaves::find($id)->update($request->all());

            if ($record) {

                return $this->response('Employee Leave successfully updated.', $record, true);
            } else {
                return $this->response('Employee Leave cannot update.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function destroy(EmployeeLeaves $EmployeeLeaves, $id)
    {

        if (EmployeeLeaves::find($id)->delete()) {

            return $this->response('Employee Leave successfully deleted.', null, true);
        } else {
            return $this->response('Employee Leave cannot delete.', null, false);
        }
    }
    public function search(Request $request, $key)
    {
        return $this->getDefaultModelSettings($request)->where('title', 'LIKE', "%$key%")->paginate($request->per_page ?? 100);
    }
    public function deleteSelected(Request $request)
    {
        $record = EmployeeLeaves::whereIn('id', $request->ids)->delete();
        if ($record) {

            return $this->response('Employee Leave Successfully delete.', $record, true);
        } else {
            return $this->response('Employee Leave cannot delete.', null, false);
        }
    }

    public function approveLeave(Request $request, $leaveId)
    {

        $model = EmployeeLeaves::find($leaveId);
        if ($model) {
            $model->status = 1;
            $model->approve_reject_notes = $request->approve_reject_notes;
            $record = $model->save();

            //-3,date range, employee id
            //schedule_employees shift_id=2

            // $record = ScheduleEmployee::updateOrCreate(['employee_id' => $request->system_user_id, 'company_id' => $request->company_id, 'shift_id' => -3, 'shift_type_id' => $request->shift_type_id]);

            if ($record) {

                return $this->response('Employee Leave Approved Successfully.', $record, true);
            } else {
                return $this->response('Employee Leave not approved.', null, false);
            }
        } else {
            return $this->response('Employee Leave data is not available.', null, false);
        }
    }
    public function newNotifications(Request $request)
    {

        $model = EmployeeLeaves::query();
        $model->with(["leave_type", "employee.leave_group", "reporting"]);
        $model->where('company_id', $request->company_id);
        $model->where('status', 0);
        $model->where('created_at', '>=', date('Y-m-d H:i:00', strtotime('-2 minutes')));

        $data['new_leaves_data'] = $model->paginate($request->per_page ?? 100);

        $model = EmployeeLeaves::query();
        //$model->with(["leave_type", "employee.leave_group", "reporting"]);
        $model->where('company_id', $request->company_id);
        $model->where('status', 0);

        $data['total_pending_count'] = $model->count();
        $data['status'] = true;
        return $data;
    }
    public function newEmployeeNotifications(Request $request)
    {

        $model = EmployeeLeaves::query();
        $model->with(["leave_type", "employee.leave_group", "reporting"]);
        $model->where('company_id', $request->company_id);
        $model->where('employee_id', $request->employee_id);
        $model->where('status', '>', 0);
        $model->where('created_at', '>=', date('Y-m-d H:i:00', strtotime('-2 minutes')));

        $data['new_leaves_data'] = $model->paginate($request->per_page ?? 100);

        $model = EmployeeLeaves::query();
        $model->with(["leave_type", "employee.leave_group", "reporting"]);
        $model->where('company_id', $request->company_id);
        $model->where('employee_id', $request->employee_id);
        $model->where('status', 0);

        $data['total_pending_count'] = $model->count();
        $data['status'] = true;
        return $data;
    }
    public function rejectLeave(Request $request, $leaveId)
    {
        $model = EmployeeLeaves::find($leaveId);
        if ($model) {
            $model->status = 2;
            $model->approve_reject_notes = $request->approve_reject_notes;
            $record = $model->save();

            if ($record) {

                return $this->response('Employee Leave Rejected Successfully.', $record, true);
            } else {
                return $this->response('Employee Leave not Rejected.', null, false);
            }
        } else {
            return $this->response('Employee Leave data is not available.', null, false);
        }
    }

}
