<?php

namespace App\Http\Controllers;

use App\Models\PayrollFormula;
use App\Http\Requests\PayrollFormula\StoreRequest;


class PayrollFormulaController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        $data = $request->validated();

        try {
            $record = PayrollFormula::updateOrCreate(["company_id" => $data['company_id']], $data);

            if ($record) {
                return $this->response('Payroll formula successfully added.', $record, true);
            } else {
                return $this->response('Payroll formula cannot add.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PayrollFormula  $payrollFormula
     * @return \Illuminate\Http\Response
     */

    public function show($id)
    {
        return PayrollFormula::where("company_id", $id)->first();
    }
}
