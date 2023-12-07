<?php

namespace App\Http\Controllers\Reports;

use App\Models\Shift;
use App\Models\Device;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ShiftType;
use App\Models\Attendance;
use App\Models\Department;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;

class WeeklyController extends Controller
{
    public function weekly(Request $request)
    {

        return $this->processPDF($request)->stream();
    }
    public function weekly_download_pdf(Request $request)
    {
        return $this->processPDF($request)->download();
    }

    public function multi_in_out_weekly_download_pdf(Request $request)
    {
        return $this->processPDF($request)->download();
    }

    public function multi_in_out_weekly_pdf(Request $request)
    {
        // return $this->processPDF($request);
        return $this->processPDF($request)->stream();
    }

    public function weekly_download_csv(Request $request)
    {
        $data = (new Attendance)->processAttendanceModel($request)->get();

        $fileName = 'report.csv';

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            $i = 0;

            fputcsv($file, ["#", "Date", "E.ID", "Name", "Dept", "Shift Type", "Shift", "Status", "In", "Out", "Total Hrs", "OT", "Late coming", "Early Going", "D.In", "D.Out"]);
            foreach ($data as $col) {
                fputcsv($file, [
                    ++$i,
                    $col['date'],
                    $col['employee_id'] ?? "---",
                    $col['employee']["display_name"] ?? "---",
                    $col['employee']["department"]["name"] ?? "---",
                    $col["shift_type"]["name"] ?? "---",
                    $col["shift"]["name"] ?? "---",
                    $col["status"] ?? "---",
                    $col["in"] ?? "---",
                    $col["out"] ?? "---",
                    $col["total_hrs"] ?? "---",
                    $col["ot"] ?? "---",
                    $col["late_coming"] ?? "---",
                    $col["early_going"] ?? "---",
                    $col["device_in"]["short_name"] ?? "---",
                    $col["device_out"]["short_name"] ?? "---"
                ], ",");
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function multi_in_out_weekly_download_csv(Request $request)
    {
        $data = (new Attendance)->processAttendanceModel($request)->get();

        $fileName = 'report.csv';

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            $i = 0;
            fputcsv($file, [
                "#", "Date", "E.ID", "Name", "In1", "Out1", "In2", "Out2", "In3", "Out3", "In4", "Out4", "In5", "Out5", "In6", "Out6", "In7", "Out7", "Total Hrs", "OT", "Status"
            ]);
            foreach ($data as $col) {
                fputcsv($file, [
                    ++$i, $col['date'], $col['employee_id'] ?? "---", $col['employee']["display_name"] ?? "---", $col["in1"] ?? "---", $col["out1"] ?? "---", $col["in2"] ?? "---", $col["out2"] ?? "---", $col["in3"] ?? "---", $col["out3"] ?? "---", $col["in4"] ?? "---", $col["out4"] ?? "---", $col["in5"] ?? "---", $col["out5"] ?? "---", $col["in6"] ?? "---", $col["out6"] ?? "---", $col["in7"] ?? "---", $col["out7"] ?? "---", $col["total_hrs"] ?? "---", $col["ot"] ?? "---", $col["status"] ?? "---",
                ], ",");
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function processPDF($request)
    {
        $model = (new Attendance)->processAttendanceModel($request);

        $data = $model->with('employee', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
            $q->select('system_user_id', 'display_name');
        })->get()->groupBy(['employee_id', 'date']);

        $company = Company::whereId($request->company_id)->with('contact:id,company_id,number')->first(["logo", "name", "company_code", "location", "p_o_box_no", "id"]);
        $company['department_name'] = DB::table('departments')->whereId($request->department_id)->first(["name"])->name ?? '';
        $company['report_type'] = $this->getStatusText($request->status);
        $company['start'] = $request->start;
        $company['end'] = $request->end;
        $collection = $model->clone()->get();

        $info = (object) [
            'total_absent' => $model->clone()->where('status', 'A')->count(),
            'total_present' => $model->clone()->where('status', 'P')->count(),
            'total_off' => $model->clone()->where('status', 'O')->count(),
            'total_missing' => $model->clone()->where('status', 'M')->count(),
            'total_early' => $model->clone()->where('early_going', '!=', '---')->count(),
            'total_hours' => $this->getTotalHours(array_column($collection->toArray(), 'total_hrs')),
            'total_ot_hours' => $this->getTotalHours(array_column($collection->toArray(), 'ot')),
            'report_type' => $request->report_type ?? "",
            'total_leave' => 0,
            'department' => Department::find($request->department_id),
            'employee' => Employee::where([
                "system_user_id" => $request->employee_id,
                "company_id" => $request->company_id,
            ])->first(),
        ];

        // if ($request->employee_id && $request->filled('employee_id')) {
        //     return Pdf::loadView('pdf.single-employee',  ['data' => $data[$request->employee_id], 'company' => $company, 'info' => $info]);
        // }
        // return $data;
        $fileName = $request->main_shift_type == 2 ? "multi-in-out" : "general";

        $arr = ['data' => $data->take(20), 'company' => $company, 'info' => $info];
        return Pdf::loadView('pdf.' . $fileName, $arr);
    }



    public function getTotalHours($times)
    {
        $sum_minutes = 0;
        foreach ($times as $time) {
            if ($time != "---") {
                $parts = explode(":", $time);
                $hours = intval($parts[0]);
                $minutes = intval($parts[1]);
                $sum_minutes += $hours * 60 + $minutes;
            }
        }
        $work_hours = floor($sum_minutes / 60);
        $sum_minutes -= $work_hours * 60;
        return $work_hours . ':' . $sum_minutes;
    }
}
