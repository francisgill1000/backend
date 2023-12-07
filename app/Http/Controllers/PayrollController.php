<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payroll\StoreRequest;
use App\Models\Payroll;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function store(StoreRequest $request, Payroll $model)
    {
        $data = $request->validated();

        $where = ["company_id" => $data['company_id'], "employee_id" => $data['employee_id']];

        try {
            $record = $model->updateOrCreate($where, $data);

            if ($record) {
                return $this->response('Payroll successfully added.', $record, true);
            } else {
                return $this->response('Payroll cannot add.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function show(Payroll $model, Request $request, $id)
    {
        $where = ["company_id" => $request->company_id, "employee_id" => $id];
        return $model->where($where)->first();
        //return $model->with(["leave_group"])->where($where)->first();
    }
}
