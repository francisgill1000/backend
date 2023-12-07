<?php

namespace App\Http\Controllers;

use App\Http\Requests\HostCompany\Store;
use App\Http\Requests\HostCompany\Update;
use App\Models\HostCompany;
use Illuminate\Http\Request;

class HostCompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function host_company_list(Request $request)
    {
        $model = HostCompany::query();

        $model->where("company_id", $request->input("company_id", 0));

        return $model->get();
    }


    public function index(Request $request)
    {
        $model = HostCompany::query();

        $fields = ['flat_number', 'company_name', 'manager_name', 'phone', 'email', 'zone_id'];

        $model = $this->process_ilike_filter($model, $request, $fields);

        $model->where("company_id", $request->input("company_id"));

        return $model->paginate($request->input("per_page", 100));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Store $request)
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $ext = $file->getClientOriginalExtension();
            $fileName = time() . '.' . $ext;
            $request->logo->move(public_path('media/company/logo/'), $fileName);
            $data['logo'] = $fileName;
        }

        try {

            $host = HostCompany::create($data);
            if (!$host) {
                return $this->response('Host cannot add.', null, false);
            }

            $host->logo = asset('media/company/logo' . $host->logo);

            return $this->response('Host successfully created.', $host, true);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Host  $host
     * @return \Illuminate\Http\Response
     */
    public function update(Update $request, $id)
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $ext = $file->getClientOriginalExtension();
            $fileName = time() . '.' . $ext;
            $request->logo->move(public_path('media/company/logo/'), $fileName);
            $data['logo'] = $fileName;
        }

        try {

            $host = HostCompany::whereId($id)->update($data);
            if (!$host) {
                return $this->response('Host cannot update.', null, false);
            }

            return $this->response('Host successfully updated.', null, true);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Host  $host
     * @return \Illuminate\Http\Response
     */
    public function destroy(HostCompany $host)
    {
        return $host->delete();
    }
}
