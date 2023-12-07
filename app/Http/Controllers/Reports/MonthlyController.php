<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Roster;
use App\Models\Shift;
use App\Models\ShiftType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonthlyController extends Controller
{
    public function monthly(Request $request)
    {
        return $this->processPDF($request)->stream();
    }

    public function monthly_download_pdf(Request $request)
    {
        $report = $this->processPDF($request);
        return $report->stream();
    }

    public function multi_in_out_monthly_download_pdf(Request $request)
    {
        return $this->processPDF($request)->download();
    }

    public function multi_in_out_monthly_pdf(Request $request)
    {
        // return $report = $this->processPDF($request);
        $report = $this->processPDF($request);
        return $report->stream();
    }

    public function monthly_download_csv(Request $request)
    {
        
        $data = (new Attendance)->processAttendanceModel($request)->get();

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
                    $col["device_out"]["short_name"] ?? "---",
                ], ",");
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function multi_in_out_monthly_download_csv(Request $request)
    {
        $data = (new Attendance)->processAttendanceModel($request)->get();

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

    public function processPDF($request)
    {
        $start = $request->from_date ?? date('Y-10-01');
        $end = $request->to_date ?? date('Y-10-31');

        $companyID = $request->company_id;

        $model = Attendance::query();
        $model = $model->whereBetween('date', [$start, $end]);
        $model->where('company_id', $companyID);
        $model->orderBy('date', 'asc');

        $model->when($request->status && $request->status != "SA" && $request->status != "S", function ($q) use ($request) {
            $q->where('status', $request->status);
        });

        $model->when($request->employee_id && $request->employee_id != "", function ($q) use ($request) {
            $q->where('employee_id', $request->employee_id);
        });

        $model->when($request->department_id && $request->department_id != -1, function ($q) use ($request) {
            $ids = Employee::where("department_id", $request->department_id)->pluck("system_user_id");
            $q->whereIn('employee_id', $ids);
        });

        $data = $model->with('employee', function ($q) use ($request) {
            $q->where('company_id', $request->company_id);
            $q->select('system_user_id', 'display_name', 'department_id');
        })->get()->groupBy(['employee_id', 'date'])->take(30);

        $company = Company::whereId($request->company_id)->with('contact:id,company_id,number')->first(["logo", "name", "company_code", "location", "p_o_box_no", "id"]);
        $company['department_name'] = DB::table('departments')->whereId($request->department_id)->first(["name"])->name ?? '';
        $company['report_type'] = $this->getStatusText($request->status);
        $company['start'] = $start;
        $company['end'] = $end;
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
                "company_id" => $companyID,
            ])->first(),
        ];

        // if ($request->employee_id && $request->filled('employee_id')) {
        //     $data = count($data) > 0 ?  $data[$request->employee_id] : [];
        //     return Pdf::loadView('pdf.single-employee',  ['data' => $data, 'company' => $company, 'info' => $info]);
        // }

        $fileName = $request->main_shift_type == 2 ? "multi-in-out" : "general";

        $arr = ['data' => $data, 'company' => $company, 'info' => $info];
        return Pdf::loadView('pdf.' . $fileName, $arr);
    }

    public function getHTML($data, $company)
    {
        $mob = $company->contact->number ?? '---';
        $companyLogo = "";

        if (env('APP_ENV') !== 'local') {
            $companyLogo = $company->logo;
        } else {
            $companyLogo = getcwd() . "/upload/app-logo.jpeg";
        }

        if ($company->p_o_box_no == "null") {
            $company->p_o_box_no = "---";
        }

        //  <img src="' . getcwd() . '/upload/app-logo.jpeg" height="70px" width="200">
        // <img src="' . $companyLogo . '" height="100px" width="100">      <img src="' . $companyLogo . '" height="100px" width="100">

        return '
        <!DOCTYPE html>
            <html>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <head>
            <style>
            table { font-family: arial, sans-serif; border-collapse: collapse; border: none; width: 100%; }
            td, th { border: 1px solid #eeeeee; text-align: left; }

            th { font-size: 9px; }
            td { font-size: 7px; }

            .page-break { page-break-after: always; }
            .main-table {
                padding-right: 15px;
                padding-left: 15px;
            }
            hr {
                position: relative;
                border: none;
                height: 2px;
                background: #c5c2c2;
                padding: 0px
            }
            .title-font {
                font-family: Arial, Helvetica, sans-serif !important;
                font-size: 14px;
                font-weight: bold
            }

            .summary-header th {
                font-size: 10px
            }

            .summary-table td {
                font-size: 9px
            }

            footer {
                bottom: 0px;
                position: absolute;
                width: 100%;
            }

            #footer {
                position: fixed;
                top: 720px;
                right: 0px;
                bottom: 0px;
                text-align: center;
                font-size: 12px;
            }

            #page-bottom-line {
                position: fixed;
                right: 0px;
                bottom: -14px;
                text-align: center;
                font-size: 12px;
                counter-reset: pageTotal;

            }

            .pageCounter span {
                counter-increment: pageTotal;
            }

            #pageNumbers div:before {
                counter-increment: currentPage;
                content: "Page "counter(currentPage) " of ";
            }

            #pageNumbers div:after {
                content: counter(pageTotal);
            }
            @page {
                margin: 20px 30px 40px 50px;
            }

            .footer-main-table {
                padding-bottom: -100px;
                padding-top: -50px;
                padding-right: 15px;
                padding-left: 15px;
            }

            .main-table {
                padding-bottom: 0px;
                padding-top: 0px;
                padding-right: 15px;
                padding-left: 15px;
            }

        </style>
            </head>
            <body>

            <table style="margin-top: -20px !important;backgroundd-color:blue;padding-bottom:0px ">
            <tr>
                <td style="text-align: left;width: 300px; border :none; padding:15px;   backgrozund-color: red">
                    <div style="img">
                    <img src="' . $companyLogo . '" height="100px" width="100">
                    </div>
                </td>
                <td style="text-align: left;width: 333px; border :none; padding:15px; backgrozusnd-color:blue">
                    <div>
                        <table style="text-align: left; border :none;  ">
                            <tr style="text-align: left; border :none;">
                                <td style="text-align: center; border :none">
                                    <span class="title-font">
                                    Monthly Attendance ' . $company->report_type . ' Report
                                    </span>
                                    <hr style="width: 230px">
                                </td>
                            </tr>
                            <tr style="text-align: left; border :none;">
                                <td style="text-align: center; border :none">
                                    <span style="font-size: 11px">
                                    ' . date('d M Y', strtotime($company->start)) . ' - ' . date('d M Y', strtotime($company->end)) . ' <br>
                                       <small> Department : ' . ($company->department_name ?? '---') . '</small>
                                    </span>
                                    <hr style="width: 230px">
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style="text-align: right;width: 300px; border :none; backgrodund-color: red">


                    <table class="summary-table"
                    style="border:none; padding:0px 50px; margin-left:35px;margin-top:20px;margin-bottom:0px">
                    <tr style="text-align: left; border :none;">
                        <td style="text-align: right; border :none;font-size:10px">
                            <b>
                            ' . $company->name . '
                            </b>
                            <br>
                        </td>
                    </tr>
                    <tr style="text-align: left; border :none;">
                        <td style="text-align: right; border :none;font-size:10px">
                            <span style="margin-right: 3px"> P.O.Box: ' . ($company->p_o_box_no ?? '---') . ' </span>
                            <br>
                        </td>
                    </tr>
                    <tr style="text-align: left; border :none;">
                        <td style="text-align: right; border :none;font-size:10px">
                            <span style="margin-right: 3px">' . ($company->location ?? '---') . '</span>
                            <br>
                        </td>
                    </tr>
                    <tr style="text-align: left; border :none;">
                        <td style="text-align: right; border :none;font-size:10px">
                            <span style="margin-right: 3px"> Tel: ' . $mob . ' </span>
                            <br>
                        </td>
                    </tr>
                </table>

                    <br>
                </td>
                </td>
            </tr>
        </table>
            <hr style="margin:0px;padding:0">
            <div id="footer">
            <div class="pageCounter">
                <p></p>
                ' . $this->getPageNumbers($data) . '
            </div>
            <div id="pageNumbers" style="font-size: 9px;margin-top:5px">
                <div class="page-number"></div>
            </div>
            </div>
            <br>
            <br>
            <footer id="page-bottom-line" style="margin-top: 20000px!important;">
            <hr style="width: 100%;margin-top: 10px!important">
            <table class="footer-main-table" >
                <tr style="border :none;">
                    <td style="text-align: left;border :none;font-size:9px"><b>Device</b>: Main Entrance = MED, Back Entrance = BED</td>
                    <td style="text-align: left;border :none;font-size:9px"><b>Shift Type</b>: Manual = MA, Auto = AU, NO = NO</td>
                    <td style="text-align: left;border :none;font-size:9px"><b>Shift</b>: Morning = Mor, Evening = Eve, Evening2 = Eve2
                    </td>
                    <td style="text-align: right;border :none;font-size:9px">
                        <b>Powered by</b>: <span style="color:blue"> www.ideahrms.com</span>
                    </td>
                    <td style="text-align: right;border :none;font-size:9px">
                        Printed on :  ' . date("d-M-Y ") . '
                    </td>
                </tr>
            </table>
        </footer>
            ' . $this->renderTable($data, $company) . '
        </body>
    </html>';
    }

    public function renderTable($data, $company)
    {
        $str = "";
        $model = Device::query();
        $shiftModel = Shift::query();
        $shiftTypeModel = ShiftType::query();
        $rosterModel = Roster::query();

        foreach ($data as $eid => $row) {

            $emp = Employee::where("employee_id", $eid)->whereCompanyId($company->id)->first();

            $str .= '<div class="page-breaks">';

            $str .= '<table class="main-table" style="margin-top: 10px !important;">';
            $str .= '<tr style="text-align: left; border :1px solid black; width:120px;">';
            $str .= '<td style="text-align:left;width:120px"><b>Name</b>:' . ($emp->display_name ?? ' ---') . '</td>';
            $str .= '<td style="text-align:left;width:120px"><b>EID</b>:' . $emp->employee_id ?? '' . '</td>';
            $str .= '<td style="text-align:left;width:120px"><b>Total Hrs</b>:' . $this->getCalculation($row)['work'] . '</td>';
            $str .= '<td style="text-align:left;width:120px"><b>OT</b>:' . $this->getCalculation($row)['ot'] . '</td>';
            $str .= '<td style="text-align:left;color:green;width:150px"><b>Present</b>:' . ($this->getCalculation($row)['presents']) . '</td>';
            $str .= '<td style="text-align:left;color:red;width:150px"><b>Absent</b>:' . ($this->getCalculation($row)['absents']) . '</td>';
            $str .= '<td style="text-align:left;color:orange"><b>Missing</b>:' . ($this->getCalculation($row)['missings']) . '</td>';
            $str .= '<td style="text-align:left;width:120px;"><b>Manual</b>:' . ($this->getCalculation($row)['manuals']) . '</td>';
            $str .= '</tr>';
            $str .= '</table>';

            $str .= '<table class="main-table" style="margin-top: 5px !important;  padding-bottom: 1px;">';

            $dates = '<tr"><td><b>Dates</b></td>';
            $days = '<tr"><td><b>Days</b></td>';
            $in = '<tr"><td><b>In</b></td>';
            $out = '<tr"><td><b>Out</b></td>';
            $work = '<tr"><td><b>Work</b></td>';
            $ot = '<tr"><td><b>OT</b></td>';
            $roster = '<tr"><td><b>Roster</b></td>';
            // $shift_type = '<tr "><td><b>Shift Type</b></td>';
            // $din = '<tr"><td><b>Device In</b></td>';
            // $dout = '<tr"><td><b>Device Out</b></td>';
            $status_tr = '<tr"><td><b>Status</b></td>';

            foreach ($row as $key => $record) {

                // dd($record[0]['roster_id']);
                if ($record[0]['roster_id'] != '---') {
                    $roster_name = $rosterModel->where("id", $record[0]['roster_id'])->first()->name ?? "";
                } else {
                    $roster_name = '---';
                }

                if ($record[0]['shift_id'] != '---') {
                    $shift_name = $shiftModel->where("id", $record[0]['shift_id'])->first()->name ?? "";
                } else {
                    $shift_name = '---';
                }

                if ($record[0]['shift_type_id'] != '---') {
                    $shift_type_name = $shiftTypeModel->where("id", $record[0]['shift_type_id'])->first()->name ?? '';
                } else {
                    $shift_type_name = '---';
                }

                // $shift_name =  $shiftModel->where("id", $record[0]['shift_id'])->first()->name ?? '';
                // $shift_type_name =  $shiftTypeModel->where("id", $record[0]['shift_type_id'])->first()->name ?? '';

                $device_short_name_in = $model->clone()->where("device_id", $record[0]['device_id_in'])->first()->short_name ?? '';
                $device_short_name_out = $model->clone()->where("device_id", $record[0]['device_id_out'])->first()->short_name ?? '';

                $dates .= '<td style="text-align: center;"> ' . substr($key, 0, 2) . ' </td>';
                $days .= '<td style="text-align: center;"> ' . $record[0]['day'] . ' </td>';

                $in .= '<td style="text-align: center;"> ' . $record[0]['in'] . ' </td>';
                $out .= '<td style="text-align: center;"> ' . $record[0]['out'] . ' </td>';

                $work .= '<td style="text-align: center;"> ' . $record[0]['total_hrs'] . ' </td>';
                $ot .= '<td style="text-align: center;"> ' . $record[0]['ot'] . ' </td>';

                $roster .= '<td style="text-align: center;"> ' . $roster_name . ' </td>';
                // $shift_type .= '<td style="text-align: center;"> ' . $shift_type_name . ' </td>';
                // $din .= '<td style="text-align: center;"> ' . $device_short_name_in . ' </td>';
                // $dout .= '<td style="text-align: center;"> ' . $device_short_name_out . ' </td>';

                $status = $record[0]['status'] == 'A' ? 'red' : 'green';

                $status_tr .= '<td style="text-align: center; color:' . $status . '"> ' . $record[0]['status'] . ' </td>';
            }

            $dates .= '</tr>';
            $days .= '</tr>';
            $in .= '</tr>';
            $out .= '</tr>';
            $work .= '</tr>';
            $ot .= '</tr>';
            $roster .= '</tr>';
            // $shift_type .= '</tr>';
            // $din .= '</tr>';
            // $dout .= '</tr>';
            $status_tr .= '</tr>';

            // $str = $str . $dates . $days . $in . $out . $work . $ot . $shift . $shift_type . $din . $dout . $status_tr;
            $str = $str . $dates . $days . $in . $out . $work . $ot . $roster . $status_tr;

            $str .= '</table>';
            $str .= '</div>';
        }
        return $str;
    }

    public function getCalculation($arr)
    {
        $work_minutes = 0;
        $ot_minutes = 0;

        $presents = 0;
        $absents = 0;
        $missings = 0;
        $manuals = 0;

        foreach ($arr as $a) {
            $status = $a[0]->status;
            $work = $a[0]->total_hrs;
            $ot = $a[0]->ot;

            if ($status == 'P') {
                $presents++;
            } else if ($status == 'A') {
                $absents++;
            } else if ($status == 'ME') {
                $missings++;
            } else if ($status == '---') {
                $manuals++;
            }

            if ($work != '---') {
                list($work_hour, $work_minute) = explode(':', $work);
                $work_minutes += $work_hour * 60;
                $work_minutes += $work_minute;
            }

            if ($ot != '---' && $ot != 'NA') {
                list($ot_hour, $ot_minute) = explode(':', $ot);
                $ot_minutes += $ot_hour * 60;
                $ot_minutes += $ot_minute;
            }
        }

        $work_hours = floor($work_minutes / 60);
        $work_minutes -= $work_hours * 60;

        $ot_hours = floor($ot_minutes / 60);
        $ot_minutes -= $ot_hours * 60;

        return [
            'work' => $work_hours . ':' . $work_minutes,
            'ot' => $ot_hours . ':' . $ot_minutes,
            'presents' => $presents,
            'absents' => $absents,
            'missings' => $missings,
            'manuals' => $manuals,
        ];
    }

    public function getPageNumbers($data)
    {
        $p = count($data);
        $str = '';
        $l = $p / 4;
        if ($p <= 3) {
            $str .= '<span></span>';
        } else if ($p <= 5) {
            $str .= '<span></span><span></span>';
        } else {
            for ($a = 1; $a <= $l; $a++) {
                $str .= '<span></span>';
            }
        }
        return $str;
    }

    public function monthly_html(Request $request)
    {
        return Pdf::loadView('pdf.html.monthly.monthly_summary')->stream();
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

    public function csvPdf()
    {
        $first = true;
        $file = fopen(public_path('transactions.csv'), 'r');
        $data = [];

        // 0 => "﻿Employee ID"
        // 1 => "First Name"
        // 2 => "Department"
        // 3 => "Date"
        // 4 => "Time"
        // 5 => "Punch State"
        // 6 => "Work Code"
        // 7 => "Area Name"
        // 8 => "Serial Number"
        // 9 => "Device Name"
        // 10 => "Upload Time"

        while (($line = fgetcsv($file)) !== false) {
            if ($first) {
                $first = false;
            } else {

                $data[] = [
                    'employee_id' => $line[0],
                    'first_name' => $line[1],
                    'department' => $line[2],
                    'date' => $line[3],
                    'time' => $line[4],
                    'punch_state' => $line[5],
                    'work_code' => $line[6],
                    'area_name' => $line[7],
                    'serial_no' => $line[8],
                    'device_name' => $line[9],
                    'upload_time' => $line[10],
                ];
            }
            // $data[] = $line;
        }
        fclose($file);
        // return $data;
        return Pdf::loadView('pdf.csv', compact('data'))->stream();
    }
}
