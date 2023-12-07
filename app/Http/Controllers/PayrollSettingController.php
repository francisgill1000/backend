<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PayrollSetting;
use Illuminate\Http\Request;

class PayrollSettingController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $year = date("Y");
        $month = date("m");

        $dateObj = \DateTime::createFromFormat('Y-n-j', $year . '-' . $month . '-' . $request->date);

        $data['date'] = $dateObj->format('Y-m-d');

        try {
            $record = PayrollSetting::updateOrCreate(["company_id" => $data['company_id']], $data);

            if ($record) {
                return $this->response('Payroll generation date has been added.', $record, true);
            } else {
                return $this->response('Payroll generation date cannot add.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function show($id)
    {
        return PayrollSetting::where("company_id", $id)->first()->day_number ?? date("d");
    }
}
