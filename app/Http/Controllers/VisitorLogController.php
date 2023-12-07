<?php

namespace App\Http\Controllers;

use App\Http\Requests\VisitorLog\Store;
use App\Models\Device;
use App\Models\Visitor;
use App\Models\VisitorLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class VisitorLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(VisitorLog $model, Request $request)
    {

        $data = $model->where("company_id", $request->company_id)
            ->with('visitor', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })

            ->whereHas('visitor', fn (Builder $query) => $query->where('company_id', $request->company_id))

            ->with('device', function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })
            ->when($request->from_date, function ($query) use ($request) {
                return $query->whereDate('LogTime', '>=', $request->from_date);
            })
            ->when($request->to_date, function ($query) use ($request) {
                return $query->whereDate('LogTime', '<=', $request->to_date);
            })

            ->when($request->UserID, function ($query) use ($request) {
                return $query->where('UserID', $request->UserID);
            })

            ->when($request->DeviceID, function ($query) use ($request) {
                return $query->where('DeviceID', $request->DeviceID);
            })

            ->when($request->filled('LogTime'), function ($q) use ($request) {
                $q->where('LogTime', 'LIKE', "$request->LogTime%");
            })
            ->when($request->filled('device'), function ($q) use ($request) {

                $q->where('DeviceID', $request->device);
            })
            ->when($request->filled('devicelocation'), function ($q) use ($request) {
                if ($request->devicelocation != 'All Locations') {

                    $q->whereHas('device', fn (Builder $query) => $query->where('location', 'ILIKE', "$request->devicelocation%"));
                }
            })
            ->when($request->filled('reason'), function ($q) use ($request) {

                $key = strtolower($request->reason);

                $q->whereHas('visitor', fn (Builder $query) => $query->where('reason', 'ILIKE', "$key%"));
            })

            ->when($request->filled('sortBy'), function ($q) use ($request) {
                $sortDesc = $request->input('sortDesc');
                if (strpos($request->sortBy, '.')) {
                    if ($request->sortBy == 'device.name') {
                        $q->orderBy(Device::select("name")->where("company_id", $request->company_id)->whereColumn("devices.device_id", "attendance_logs.DeviceID"), $sortDesc == 'true' ? 'desc' : 'asc');
                    } else if ($request->sortBy == 'device.location') {
                        $q->orderBy(Device::select("location")->where("company_id", $request->company_id)->whereColumn("devices.device_id", "attendance_logs.DeviceID"), $sortDesc == 'true' ? 'desc' : 'asc');
                    }
                } else {
                    $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc'); {
                    }
                }
            });
        if (!$request->sortBy) {
            $data->orderBy('LogTime', 'DESC');
        }
        return $data->paginate($request->per_page);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Store $request)
    {
        try {

            VisitorLog::create($request->validated());

            return $this->response("Log Successfully Added", null, true);
        } catch (\Exception $e) {
            return $this->response("Log cannot Add. Details:" . $e->getMessage(), null, false, 422);
        }
    }



    /**
     * Display the specified resource.
     *
     * @param  \App\Models\VisitorLog  $visitorLog
     * @return \Illuminate\Http\Response
     */
    public function show(VisitorLog $visitorLog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\VisitorLog  $visitorLog
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, VisitorLog $visitorLog)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\VisitorLog  $visitorLog
     * @return \Illuminate\Http\Response
     */
    public function destroy(VisitorLog $visitorLog)
    {
        //
    }
}
