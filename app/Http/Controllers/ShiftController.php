<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shift\StoreRequest;
use App\Http\Requests\Shift\UpdateRequest;
use App\Http\Requests\Shift\UpdateSingleShiftRequest;
use App\Models\AutoShift;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $model = Shift::query();
        $model->with("shift_type");
        $model->where('company_id', $request->company_id);
        $model->when($request->filled('name'), function ($q) use ($request) {
            //$key = strtolower($request->name);
            $q->where('name', 'ILIKE', "$request->name%");
        });
        $model->when($request->filled('shift_type_name'), function ($q) use ($request) {
            //$key = strtolower($request->search_shift_type);
            $q->whereHas('shift_type', fn(Builder $query) => $query->where('name', 'ILIKE', "$request->shift_type_name%"));
        });

        return $model->paginate($request->per_page);
    }

    public function list_with_out_multi_in_out(Request $request)
    {
        $model = Shift::query();
        $model->whereHas("shift_type", function ($q) {
            $q->where("id", "!=", 2);
        });
        $model->with("shift_type");
        $model->where('company_id', $request->company_id);
        return $model->paginate($request->per_page);
    }

    public function shift_by_type(Request $request)
    {
        return Shift::with("shift_type")->where("company_id", $request->company_id)->where("shift_type_id", $request->shift_type_id)->get();
    }

    public function shift_by_types(Request $request)
    {
        return Shift::with("shift_type")->where("company_id", $request->company_id)->whereIn("shift_type_id", [1, 2, 4, 5, 6])->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request, Shift $model)
    {
        if ($request->shift_type_id == 3) {
            return $this->processAutoShift($request->shift_ids);
        }

        try {
            $record = $model->create($request->validated());

            if ($record) {
                return $this->response('Shift successfully added.', $record, true);
            } else {
                return $this->response('Shift cannot add.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function processAutoShift($shift_ids)
    {
        $arr = [];

        foreach ($shift_ids as $shift_id) {
            $arr[] = [
                "shift_id" => $shift_id,
            ];
        }

        try {
            $record = AutoShift::insert($arr);

            if ($record) {
                return $this->response('Shift successfully added.', $record, true);
            } else {
                return $this->response('Shift cannot add.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Shift  $Shift
     * @return \Illuminate\Http\Response
     */
    public function show(Shift $Shift)
    {
        return $Shift;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Shift  $Shift
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Shift $Shift)
    {
        try {
            $record = $Shift->update($request->validated());

            if ($record) {
                return $this->response('Shift successfully updated.', $record, true);
            } else {
                return $this->response('Shift cannot update.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Shift  $Shift
     * @return \Illuminate\Http\Response
     */
    public function destroy(Shift $Shift)
    {
        try {
            if ($Shift->delete()) {
                return $this->response('Shift successfully updated.', null, true);
            } else {
                return $this->response('Shift cannot update.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getShift(Request $request)
    {
        return $model = Shift::where('id', $request->id)->find($request->id)->makeHidden('shift_type');
        $model->where('company_id', $request->company_id);
        return $model->paginate($request->per_page);
    }

    public function updateSingleShift(UpdateSingleShiftRequest $request)
    {
        try {
            $model = Shift::find($request->id);
            $data = $request->validated();
            $data['shift_type_id'] = 2;
            $record = $model->update($data);
            if ($record) {
                return $this->response('Shift successfully updated.', $record, true);
            } else {
                return $this->response('Shift cannot update.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
