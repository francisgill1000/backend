<?php

namespace App\Http\Controllers;

use App\Http\Requests\Leavegroups\StoreRequest;
use App\Http\Requests\Leavegroups\UpdateRequest;
use App\Models\EmployeeLeaves;
use App\Models\LeaveCount;
use App\Models\LeaveGroups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveGroupsController extends Controller
{
    public function getDefaultModelSettings($request, $id = '')
    {
        $model = LeaveGroups::query();
        $model->with(["leave_count.leave_type"]);
        $model->where('company_id', $request->company_id);
        if ($id > 0) {
            $model->where('id', $id);
        }

        // $model->when($request->filled('employee_id'), function ($q) use ($request) {
        //     $key = $request->serach_name;
        //     $q->where('name', 'ILIKE', "$key%");
        // });
        // $model->when($request->filled('search_short_name'), function ($q) use ($request) {
        //     $key = $request->search_short_name;
        //     $q->where('short_name', 'ILIKE', "$key%");
        // });

        return $model;
    }

    public function index(Request $request)
    {

        return $this->getDefaultModelSettings($request)->paginate($request->per_page ?? 100);
    }

    public function getLeaveGroupById(Request $request, $id)
    {
        return $this->getDefaultModelSettings($request)->paginate($request->per_page ?? 100);
    }
    public function show($id, Request $request)
    {

        $year = date("Y");
        $data = LeaveGroups::with(["leave_count.leave_type"])->whereId($id)->get();
        if ($request->filled('employee_id')) {

            foreach ($data as $key => $value) {

                foreach ($value->leave_count as $key2 => $value2) {

                    $leaves_count = EmployeeLeaves::where('company_id', '=', $request->company_id)
                        ->where('leave_type_id', '=', $value2->leave_type_id)
                        ->where('employee_id', '=', $request->employee_id)
                        ->where('status', '=', 1)->count();

                    $value2->employee_used = $leaves_count;
                    $value2->year = $year;
                }

            }

            return $data;

        } else {
            return $data;
        }

    }

    function list(Request $request) {
        return $this->getDefaultModelSettings($request)->paginate($request->per_page ?? 100);
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();

        try {
            // Database operations

            $isExist = LeaveGroups::where('company_id', '=', $request->company_id)->where('group_name', '=', $request->group_name)->first();
            if ($isExist == null) {

                $record = LeaveGroups::create($request->all());

                DB::commit();
                if ($record) {

                    return $this->response('Leave Group  Successfully created.', $record, true);
                } else {
                    return $this->response('Leave Group cannot be created.', null, false);
                }
            } else {
                return $this->response('Leave Group "' . $request->group_name . '" already exist', null, false);
            }
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }
    public function update(UpdateRequest $request, $id)
    {

        try {
            $isExist = LeaveGroups::where('company_id', '=', $request->company_id)
                ->where('group_name', '=', $request->group_name)
                ->where('id', '!=', $id)
                ->first();
            if ($isExist == null) {

                $record = LeaveGroups::find($id)->update($request->all());

                if ($record) {

                    return $this->response('Leave Group successfully updated.', $record, true);
                } else {
                    return $this->response('Leave Group cannot update.', null, false);
                }
            } else {
                return $this->response('Leave Group "' . $request->group_name . '" already exist', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function destroy(LeaveGroups $LeaveGroups, $id)
    {

        if (LeaveGroups::find($id)->delete()) {

            LeaveCount::where('group_id', '=', $id)->delete();

            return $this->response('Leave Groups   successfully deleted.', null, true);
        } else {
            return $this->response('Leave Groups   cannot delete.', null, false);
        }
    }
    public function search(Request $request, $key)
    {
        return $this->getDefaultModelSettings($request)->where('title', 'LIKE', "%$key%")->paginate($request->per_page ?? 100);
    }
    public function deleteSelected(Request $request)
    {
        $record = LeaveGroups::whereIn('id', $request->ids)->delete();
        if ($record) {

            return $this->response('Leave Groups Successfully delete.', $record, true);
        } else {
            return $this->response('Leave Groups cannot delete.', null, false);
        }
    }

}