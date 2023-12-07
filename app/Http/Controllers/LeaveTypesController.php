<?php

namespace App\Http\Controllers;

use App\Http\Requests\Leavetype\StoreRequest;
use App\Http\Requests\Leavetype\UpdateRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveTypesController extends Controller
{
    public function getDefaultModelSettings($request)
    {
        $model = LeaveType::query();
        $model->where('company_id', $request->company_id);

        $model->when($request->filled('serach_name'), function ($q) use ($request) {
            $key = $request->serach_name;
            $q->where('name', 'ILIKE', "$key%");
        });
        $model->when($request->filled('search_short_name'), function ($q) use ($request) {
            $key = $request->search_short_name;
            $q->where('short_name', 'ILIKE', "$key%");
        });

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

            $isExist = LeaveType::where('company_id', '=', $request->company_id)->where('name', '=', $request->name)->first();
            if ($isExist == null) {

                $record = LeaveType::create($request->all());
                DB::commit();

                if ($record) {

                    return $this->response('Leave Type  Successfully created.', $record, true);
                } else {
                    return $this->response('Leave Type  cannot be created.', null, false);
                }
            } else {
                return $this->response('Leave Type "' . $request->name . '" already exist', null, false);
            }
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }
    public function update(UpdateRequest $request, $id)
    {

        try {
            $isExist = LeaveType::where('company_id', '=', $request->company_id)
                ->where('id', '!=', $id)
                ->where('name', '=', $request->name)->first();
            if ($isExist == null) {

                $record = LeaveType::find($id)->update($request->all());

                if ($record) {

                    return $this->response('Leave Type  successfully updated.', $record, true);
                } else {
                    return $this->response('Leave Type  cannot update.', null, false);
                }
            } else {
                return $this->response('Leave Type "' . $request->name . '" already exist', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function destroy($id)
    {

        if (LeaveType::find($id)->delete()) {

            return $this->response('LeaveType successfully deleted.', null, true);
        } else {
            return $this->response('LeaveType cannot delete.', null, false);
        }
    }
    public function search(Request $request, $key)
    {
        return $this->getDefaultModelSettings($request)->where('title', 'LIKE', "%$key%")->paginate($request->per_page ?? 100);
    }
    public function deleteSelected(Request $request)
    {
        $record = LeaveType::whereIn('id', $request->ids)->delete();
        if ($record) {

            return $this->response('LeaveType Successfully delete.', $record, true);
        } else {
            return $this->response('LeaveType cannot delete.', null, false);
        }
    }

}
