<?php

namespace App\Http\Controllers;

use App\Http\Requests\Visitor\Store;
use App\Http\Requests\Visitor\Update;
use App\Jobs\ProcessSDKCommand;
use App\Models\Visitor;
use App\Models\Zone;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function visitors_with_type(Request $request)
    {
        $model = Visitor::query();

        $model->where("company_id", $request->input("company_id"));

        return $model->get();
    }

    public function index(Request $request)
    {
        $model = Visitor::query();

        $fields = ['id', 'company_name', 'system_user_id', 'manager_name', 'phone', 'email', 'zone_id'];

        $model = $this->process_ilike_filter($model, $request, $fields);

        $model->where("company_id", $request->input("company_id"));

        return $model->with(["status", "zone"])->paginate($request->input("per_page", 100));
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
            $request->logo->move(public_path('media/visitor/logo/'), $fileName);
            $data['logo'] = $fileName;
        }

        try {

            $visitor = Visitor::create($data);

            if (!$visitor) {
                return $this->response('Visitor cannot add.', null, false);
            }

            $preparedJson = $this->prepareJsonForSDK($data);

            // $this->SDKCommand(env('SDK_URL') . "/Person/AddRange", $preparedJson);
            ProcessSDKCommand::dispatch(env('SDK_URL') . "/Person/AddRange", $preparedJson);

            return $this->response('Visitor successfully created.', null, true);
        } catch (\Throwable $th) {
            return $this->response('Server Error.', null, true);
        }
    }

    public function store_test(Request $request)
    {
        $data = $request->all();

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $ext = $file->getClientOriginalExtension();
            $fileName = time() . '.' . $ext;
            $request->logo->move(public_path('media/visitor/logo/'), $fileName);
            $data['logo'] = $fileName;
        }

        try {

            $preparedJson = $this->prepareJsonForSDK($data);
            // return $this->SDKCommand(env('SDK_URL') . "/Person/AddRange", $preparedJson);
            ProcessSDKCommand::dispatch(env('SDK_URL') . "/Person/AddRange", $preparedJson);
            return "francis";
        } catch (\Throwable $th) {
            return $this->response('Server Error.', null, true);
        }
    }

    public function prepareJsonForSDK($data)
    {
        $personList = [];

        $personList["name"] = $data["first_name"] . " " . $data["last_name"];
        $personList["userCode"] = $data["system_user_id"];
        $personList["timeGroup"] = $data["timezone_id"];


        if (env("APP_ENV") == "local") {
            $personList["faceImage"] =  "https://stagingbackend.ideahrms.com/media/employee/profile_picture/1686330253.jpg";
        } else {
            $personList["faceImage"] =  asset('media/visitor/logo/' . $data['logo']);
        }

        $zoneDevices = Zone::with(["devices"])->find($data['zone_id']);

        return [
            "snList" => collect($zoneDevices->devices)->pluck("device_id"),
            "personList" => [$personList],
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Visitor  $visitor
     * @return \Illuminate\Http\Response
     */
    public function update(Update $request, $id)
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $ext = $file->getClientOriginalExtension();
            $fileName = time() . '.' . $ext;
            $request->logo->move(public_path('media/visitor/logo/'), $fileName);
            $data['logo'] = $fileName;
        }

        try {

            $visitor = Visitor::whereId($id)->update($data);
            if (!$visitor) {
                return $this->response('Visitor cannot update.', null, false);
            }

            return $this->response('Visitor successfully updated.', null, true);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Visitor  $visitor
     * @return \Illuminate\Http\Response
     */
    public function destroy(Visitor $visitor)
    {
        return $visitor->delete();
    }
}
