<?php

namespace App\Http\Controllers;

use App\Http\Requests\Device\StoreRequest;
use App\Http\Requests\Device\UpdateRequest;
use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $model = Device::query();
        $cols = $request->cols;
        $model->with(['status', 'company']);
        $model->where('company_id', $request->company_id);
        $model->when($request->filled('name'), function ($q) use ($request) {
            $q->where('name', 'ILIKE', "$request->name%");
        });
        $model->when($request->filled('short_name'), function ($q) use ($request) {
            $q->where('short_name', 'ILIKE', "$request->short_name%");
        });
        $model->when($request->filled('location'), function ($q) use ($request) {
            $q->where('location', 'ILIKE', "$request->location%");
        });
        $model->when($request->filled('device_id'), function ($q) use ($request) {
            $q->where('device_id', 'ILIKE', "%$request->device_id%");
        });
        $model->when($request->filled('device_type'), function ($q) use ($request) {
            $q->where('device_type', 'ILIKE', "$request->device_type%");
        });
        $model->when($request->filled('Status'), function ($q) use ($request) {
            $q->where('status_id', $request->Status);
        });

        // array_push($cols, 'status.id');

        $model->when(isset($cols) && count($cols) > 0, function ($q) use ($cols) {
            $q->select($cols);
        });

        $model->when($request->filled('sortBy'), function ($q) use ($request) {
            $sortDesc = $request->input('sortDesc');
            if (strpos($request->sortBy, '.')) {
                // if ($request->sortBy == 'department.name.id') {
                //     $q->orderBy(Department::select("name")->whereColumn("departments.id", "employees.department_id"), $sortDesc == 'true' ? 'desc' : 'asc');

                // }

            } else {
                $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc'); {
                }
            }
        });
        return $model->paginate($request->per_page ?? 1000);

        //return $model->with(['status', 'company'])->where('company_id', $request->company_id)->paginate($request->per_page ?? 1000);
    }

    public function getDeviceList(Device $model, Request $request)
    {
        return $model->with(['status'])->where('company_id', $request->company_id)->get();
    }

    public function store(Device $model, StoreRequest $request)
    {

        // $record = false;
        try {
            // $response = Http::post(env("LOCAL_IP") .':'. env("LOCAL_PORT") . '/Register', [
            //     'sn' => $request->device_id, //OX-8862021010010
            //     'ip' => $request->ip,
            //     'port' => $request->port,
            // ]);

            // if ($response->status() == 200) {
            //     $record = $model->create($request->validated());
            // }

            $record = $model->create($request->validated());

            if ($record) {
                return $this->response('Device successfully added.', $record, true);
            } else {
                return $this->response('Device cannot add.', null, 'device_api_error');
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function show(Device $model, $id)
    {
        return $model->with(['status', 'company'])->find($id);
    }

    public function getDeviceCompany(Request $request)
    {
        $device = DB::table("devices")->where("company_id", $request->company_id)->where("device_id", $request->SN)->first(['name as device_name', 'short_name', 'device_id', 'location', "company_id"]);
        $model = DB::table("employees")->where("company_id", $request->company_id)->where("system_user_id", $request->UserCode)->first(['first_name', 'display_name', 'profile_picture']);

        if ($model && $model->profile_picture) {
            $model->profile_picture = asset('media/employee/profile_picture/' . $model->profile_picture);
        }

        return [
            "UserID" => $request->UserCode,
            "time" => date("H:i", strtotime($request->RecordDate)),
            "employee" => $model,
            "device" => $device,
        ];
    }
    public function getLastRecordsHistory($id = 0, $count = 0, Request $request)
    {

        // return Employee::select("system_user_id")->where('company_id', $request->company_id)->get();

        $model = AttendanceLog::query();
        $model->with(array('employee' => function ($query) use ($request) {
            $query->where('company_id', $request->company_id);
        }))->first();
        $model->with(['device']);
        $model->where('company_id', $id);

        $model->whereIn('UserID', function ($query) use ($request) {
            // $model1 = Employee::query();
            // $model1->select("system_user_id")->where('employees.company_id', $request->company_id);

            $query->select('system_user_id')->from('employees')->where('employees.company_id', $request->company_id);
        });

        $model->when($request->filled('search_time'), function ($q) use ($request) {
            $key = date('Y-m-d') . ' ' . $request->search_time;
            $q->Where('LogTime', 'LIKE', "$key%");
        });
        $model->when($request->filled('search_device_id'), function ($q) use ($request) {
            $key = strtoupper($request->search_device_id);
            //$q->Where(DB::raw('lower(DeviceID)'), 'LIKE', "$key%");
            $q->Where('DeviceID', 'LIKE', "$key%");
        });
        // $model->when($request->filled('sortBy'), function ($q) use ($request) {

        //     $sortDesc = $request->input('sortDesc');
        //     if (strpos($request->sortBy, '.')) {
        //         if ($request->sortBy == 'employee.first_name') {
        //             $q->orderBy(Employee::select("first_name")->where('employees.company_id', $request->company_id)->whereColumn("employees.system_user_id", "attendance_logs.UserID"), $sortDesc == 'true' ? 'desc' : 'asc');
        //         }
        //         if ($request->sortBy == 'employee.department') {
        //             // $q->orderBy(Employee::with(['department' => function ($query) {
        //             //     $query->select('name');
        //             // }])->where('employees.company_id', $request->company_id)->whereColumn("employees.system_user_id", "attendance_logs.UserID"), $sortDesc == 'true' ? 'desc' : 'asc');
        //         }
        //         if ($request->sortBy == 'device.device_name') {
        //             $q->orderBy(Device::select("name")->where('devices.company_id', $request->company_id)->whereColumn("devices.device_id", "attendance_logs.DeviceID"), $sortDesc == 'true' ? 'desc' : 'asc');
        //         }
        //         if ($request->sortBy == 'device.device_id') {
        //             $q->orderBy(Device::select("device_id")->where('devices.company_id', $request->company_id)->whereColumn("devices.device_id", "attendance_logs.DeviceID"), $sortDesc == 'true' ? 'desc' : 'asc');
        //         }
        //         if ($request->sortBy == 'device.location') {
        //             $q->orderBy(Device::select("location")->where('devices.company_id', $request->company_id)->whereColumn("devices.device_id", "attendance_logs.DeviceID"), $sortDesc == 'true' ? 'desc' : 'asc');
        //         }
        //     } else {
        //         $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc');{

        //         }

        //     }

        // });
        if (!$request->sortBy) {

            $model->orderBy("LogTime", 'desc');
        }
        //$model->orderByDesc("LogTime");
        $logs = $model->paginate($request->per_page);

        return $logs;
    }
    public function getLastRecordsByCount($id = 0, $count = 0, Request $request)
    {

        // $id = 0;
        // $count = 0;
        // if ($request->id && $request->count) {
        //     $id = $request->id;
        //     $count = $request->count;
        // } else {

        //     return;
        // }

        $model = AttendanceLog::query();
        $model->where('company_id', $id);
        $model->when($request->filled('search_time'), function ($q) use ($request) {
            $key = date('Y-m-d') . ' ' . $request->search_time;
            $q->Where('LogTime', 'LIKE', "$key%");
        });
        $model->when($request->filled('search_device_id'), function ($q) use ($request) {
            $key = strtoupper($request->search_device_id);
            //$q->Where(DB::raw('lower(DeviceID)'), 'LIKE', "$key%");
            $q->Where('DeviceID', 'LIKE', "$key%");
        });
        $model->take($count);
        $model->orderByDesc("id");

        $logs = $model->get(["UserID", "LogTime", "DeviceID"]);

        $arr = [];

        foreach ($logs as $log) {

            $employee = Employee::withOut(['schedule', 'department', 'sub_department', 'designation', 'user', 'role'])
                ->where('company_id', $id)
                ->where('system_user_id', $log->UserID)
                // ->when($request->filled('search_employee_name'), function ($q) use ($request) {

                //     $key = strtolower($request->search_employee_name);
                //     $q->where(function ($q) use ($key) {
                //         $q->Where(DB::raw('lower(first_name)'), 'LIKE', "$key%");
                //         $q->orWhere(DB::raw('lower(last_name)'), 'LIKE', "$key%");
                //     });
                // })
                // ->when($request->filled('search_system_user_id'), function ($q) use ($request) {
                //     $key = strtolower($request->search_system_user_id);
                //     $q->Where(DB::raw('lower(system_user_id)'), 'LIKE', "$key%");
                // })
                // ->when($request->filled('search_employee_id'), function ($q) use ($request) {
                //     $key = strtolower($request->search_employee_id);
                //     $q->Where(DB::raw('lower(employee_id)'), 'LIKE', "$key%");
                // })
                // ->when($request->filled('search_employee_id'), function ($q) use ($request) {
                //     $key = strtolower($request->search_employee_id);
                //     $q->Where(DB::raw('lower(employee_id)'), 'LIKE', "$key%");
                // })

                ->first(['first_name', 'last_name', 'employee_id', 'display_name', 'profile_picture', 'company_id']);

            $dev = Device::where('device_id', $log->DeviceID)
                ->when($request->filled('search_device_name'), function ($q) use ($request) {
                    $key = strtolower($request->search_device_name);
                    $q->Where('name', 'LIKE', "$key%");
                })
                ->first(['name as device_name', 'short_name', 'device_id', 'location']);

            if ($employee) {
                $arr[] = [
                    "company_id" => $employee->company_id,
                    "UserID" => $log->UserID,
                    "time" => date("H:i", strtotime($log->LogTime)),
                    "device" => $dev,
                    "LogTime" => $log->LogTime,
                    "employee" => $employee,
                ];
            }
        }

        return $arr;

        // Cache::forget("last-five-logs");
        return Cache::remember('last-five-logs', 300, function () use ($id, $count) {

            $model = AttendanceLog::query();
            $model->where('company_id', $id);
            $model->take($count);

            $logs = $model->get(["UserID", "LogTime", "DeviceID"]);

            $arr = [];

            foreach ($logs as $log) {

                $employee = Employee::withOut(['schedule', 'department', 'sub_department', 'designation', 'user', 'role'])
                    ->where('company_id', $id)
                    ->where('system_user_id', $log->UserID)
                    ->first(['first_name', 'profile_picture', 'company_id']);

                $dev = Device::where('device_id', $log->DeviceID)
                    ->first(['name as device_name', 'short_name', 'device_id', 'location']);

                if ($employee) {
                    $arr[] = [
                        "company_id" => $employee->company_id,
                        "UserID" => $log->UserID,
                        "time" => date("H:i", strtotime($log->LogTime)),
                        "device" => $dev,
                        "employee" => $employee,
                    ];
                }
            }

            return $arr;
        });
    }

    public function update(Device $Device, UpdateRequest $request)
    {
        try {
            $record = $Device->update($request->validated());

            if ($record) {
                return $this->response('Device successfully updated.', $record, true);
            } else {
                return $this->response('Device cannot update.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function destroy(Device $device)
    {
        try {
            $record = $device->delete();

            if ($record) {
                return $this->response('Device successfully deleted.', $record, true);
            } else {
                return $this->response('Device cannot delete.', null, 'device_api_error');
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function search(Request $request, $key)
    {
        $model = Device::query();

        $fields = [
            'name', 'device_id', 'location', 'short_name',
            'status' => ['name'],
            'company' => ['name'],
        ];

        $model = $this->process_search($model, $key, $fields);

        $model->with(['status', 'company']);

        return $model->paginate($request->per_page);
    }

    public function deleteSelected(Device $model, Request $request)
    {
        try {
            $record = $model->whereIn('id', $request->ids)->delete();

            if ($record) {
                return $this->response('Device successfully deleted.', $record, true);
            } else {
                return $this->response('Device cannot delete.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function sync_device_date_time(Request $request, $device_id)
    {
        $curl = curl_init();
        $dateTime = $request->sync_able_date_time;

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://sdk.ideahrms.com/$device_id/SyncDateTime",
            // CURLOPT_URL => "http://139.59.69.241:5000/$device_id/SyncDateTime",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{ "dateTime": "' . $dateTime . '" }',
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $result = json_decode($response);

        if ($result && $result->status == 200) {
            try {
                $record = Device::where("device_id", $device_id)->update([
                    "sync_date_time" => $request->sync_able_date_time,
                ]);

                if ($record) {
                    return $this->response('Time has been synced to the Device.', Device::with(['status', 'company'])->where("device_id", $device_id)->first(), true);
                } else {
                    return $this->response('Time cannot synced to the Device.', null, false);
                }
            } catch (\Throwable $th) {
                throw $th;
            }
        } else if ($result && $result->status == 102) {
            return $this->response("The device is not connected to the server or is not registered", $result, false);
        }

        return $this->response("Unkown Error. Please retry again after 1 min or contact to technical team", null, false);
    }

    public function devcieCountByStatus($company_id)
    {
        // Use query builder to build the queries more fluently
        $statusCounts = Device::where('company_id', $company_id)
            ->whereIn('status_id', [1, 2])
            ->selectRaw('status_id, COUNT(*) as count')
            ->groupBy('status_id')
            ->get();

        $onlineDevices = 0;
        $offlineDevices = 0;

        foreach ($statusCounts as $statusCount) {
            if ($statusCount->status_id == 1) {
                $onlineDevices = $statusCount->count;
            } elseif ($statusCount->status_id == 2) {
                $offlineDevices = $statusCount->count;
            }
        }

        return [
            "total" => $onlineDevices + $offlineDevices,
            "labels" => ["Online", "Offline"],
            "series" => [$onlineDevices, $offlineDevices],
        ];
    }
}
