<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    public function index(Request $request)
    {
    //    return $request->all();
        $model = (new Attendance)->processAttendanceModel($request);

        if ($request->main_shift_type == 1) {
            return $this->general($model, $request->per_page);
        }

        return $this->multiInOut($model->get(), $request->per_page);
    }

    public function general($model, $per_page = 100)
    {
        return $model->paginate($per_page);
    }

    public function multiInOut($model, $per_page = 100)
    {
        foreach ($model as $value) {
            $logs = $value->logs ?? [];
            $count = count($logs);
            $requiredLogs = max($count, 7); // Ensure at least 8 logs

            for ($a = 0; $a < $requiredLogs; $a++) {
                $log = $logs[$a] ?? [];
                $value["in" . ($a + 1)] = $log["in"] ?? "---";
                $value["out" . ($a + 1)] = $log["out"] ?? "---";
            }
        }

        // foreach ($model as $value) {
        //     $count = count($value->logs ?? []);
        //     if ($count > 0) {
        //         if ($count < 8) {
        //             $diff = 7 - $count;
        //             $count = $count + $diff;
        //         }
        //         for ($a = 0; $a < $count; $a++) {

        //             $holder = $a;
        //             $holder_key = ++$holder;

        //             $value["in" . $holder_key] = $value->logs[$a]["in"] ?? "---";
        //             $value["out" . $holder_key] = $value->logs[$a]["out"] ?? "---";
        //         }
        //     }
        // }

        return $this->paginate($model, $per_page);
    }

    public function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);

        $perPage == 0 ? 50 : $perPage;

        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    public function general_download_csv(Request $request)
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

    public function multi_in_out_daily_download_csv(Request $request)
    {
        $data = (new Attendance)->processAttendanceModel($request)->get();

        foreach ($data as $value) {
            $count = count($value->logs ?? []);
            if ($count > 0) {
                if ($count < 8) {
                    $diff = 7 - $count;
                    $count = $count + $diff;
                }
                $i = 1;
                for ($a = 0; $a < $count; $a++) {

                    $holder = $a;
                    $holder_key = ++$holder;

                    $value["in" . $holder_key] = $value->logs[$a]["in"] ?? "---";
                    $value["out" . $holder_key] = $value->logs[$a]["out"] ?? "---";
                }
            }
        }

        $fileName = 'report.csv';

        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0",
        );

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            $i = 0;
            fputcsv($file, [
                "#",
                "Date",
                "E.ID",
                "Name",
                "In1",
                "Out1",
                "In2",
                "Out2",
                "In3",
                "Out3",
                "In4",
                "Out4",
                "In5",
                "Out5",
                "In6",
                "Out6",
                "In7",
                "Out7",
                "Total Hrs",
                "OT",
                "Status",

            ]);
            foreach ($data as $col) {
                fputcsv($file, [
                    ++$i,
                    $col['date'],
                    $col['employee_id'] ?? "---",
                    $col['employee']["display_name"] ?? "---",
                    $col["in1"] ?? "---",
                    $col["out1"] ?? "---",
                    $col["in2"] ?? "---",
                    $col["out2"] ?? "---",
                    $col["in3"] ?? "---",
                    $col["out3"] ?? "---",
                    $col["in4"] ?? "---",
                    $col["out4"] ?? "---",
                    $col["in5"] ?? "---",
                    $col["out5"] ?? "---",
                    $col["in6"] ?? "---",
                    $col["out6"] ?? "---",
                    $col["in7"] ?? "---",
                    $col["out7"] ?? "---",
                    $col["total_hrs"] ?? "---",
                    $col["ot"] ?? "---",
                    $col["status"] ?? "---",

                ], ",");
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
