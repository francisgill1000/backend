<?php

namespace App\Http\Controllers;

use App\Http\Requests\Holidays\StoreRequest;
use App\Http\Requests\Holidays\UpdateRequest;
use App\Models\Holidays;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HolidaysController extends Controller
{
    public function getDefaultModelSettings($request)
    {
        $model = Holidays::query();
        $model->where('company_id', $request->company_id);
        $model->where('year', $request->year);

        $model->when($request->filled('serach_name'), function ($q) use ($request) {
            $key = $request->serach_name;
            $q->where('name', 'ILIKE', "$key%");
        });
        $model->when($request->filled('search_start_date'), function ($q) use ($request) {
            $key = $request->search_start_date;
            $q->where('start_date', 'ILIKE', "$key%");
        });
        $model->when($request->filled('search_end_date'), function ($q) use ($request) {
            $key = $request->search_end_date;
            $q->where('end_date', 'ILIKE', "$key%");
        });
        $model->when($request->filled('serach_total_days'), function ($q) use ($request) {
            $key = $request->serach_total_days;
            $q->where('total_days', 'ILIKE', "$key%");
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
            // Database operations
            $record = Holidays::create($request->all());

            DB::commit();
            if ($record) {

                return $this->response('Holidays Successfully created.', $record, true);
            } else {
                return $this->response('Holidays cannot be created.', null, false);
            }
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }
    public function update(UpdateRequest $request, Holidays $Holidays, $id)
    {

        try {
            $record = $Holidays::find($id)->update($request->all());

            if ($record) {

                return $this->response('Holidays successfully updated.', $record, true);
            } else {
                return $this->response('Holidays cannot update.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function destroy(Holidays $Holidays, $id)
    {

        if (Holidays::find($id)->delete()) {

            return $this->response('Holidays successfully deleted.', null, true);
        } else {
            return $this->response('Holidays cannot delete.', null, false);
        }
    }
    public function search(Request $request, $key)
    {
        return $this->getDefaultModelSettings($request)->where('title', 'LIKE', "%$key%")->paginate($request->per_page ?? 100);
    }
    public function deleteSelected(Request $request)
    {
        $record = Holidays::whereIn('id', $request->ids)->delete();
        if ($record) {

            return $this->response('Holidays Successfully delete.', $record, true);
        } else {
            return $this->response('Holidays cannot delete.', null, false);
        }
    }

}
