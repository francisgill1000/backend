<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\Employee;
use App\Models\HostCompany;
use App\Models\Leave;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class VisitorDashboard extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $date = date("Y-m-d");
        
        $id = $request->company_id ?? 0;

        $Visitors = Visitor::query();

        $Visitors->whereCompanyId($id);

        $visitorCounts = $Visitors->withCount([
            'status as total_approved' => fn ($q) =>  $q->where('code', 'A'),
            'status as total_pending' => fn ($q) => $q->where('code', 'P'),
            'status as total_canceled' => fn ($q) => $q->where('code', 'C'),
            'status as total_rejected' => fn ($q) => $q->where('code', 'R'),
        ])->get();



        return [
            "visitorCounts" => [
                [
                    "title" => "Checked In",
                    "value" => rand(10, 1000),
                    "icon" => "fas fa-calendar-check",
                    "color" => "l-bg-green-dark",
                    "link"  => env("BASE_URL") . "/api/daily?company_id=$id&status=SA&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                    "multi_in_out"  => env("BASE_URL") . "/api/multi_in_out_daily?company_id=$id&status=SA&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                ],
                [
                    "title" => "Checked Out",
                    "value" => rand(10, 1000),
                    "icon" => "fas fa-calendar-times",
                    "color" => "l-bg-purple-dark",
                    "link"  => env("BASE_URL") . "/api/daily?page=1&per_page=1000&company_id=$id&status=P&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                    "multi_in_out"  => env("BASE_URL") . "/api/multi_in_out_daily?page=1&per_page=1000&company_id=$id&status=P&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                ],
                [
                    "title" => "Expected",
                    "value" => rand(10, 1000),
                    "icon" => "fas fa-calendar-times",
                    "color" => "l-bg-orange-dark",
                    "link"  => env("BASE_URL") . "/api/daily?page=1&per_page=1000&company_id=$id&status=A&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                    "multi_in_out"  => env("BASE_URL") . "/api/multi_in_out_daily?page=1&per_page=1000&company_id=$id&status=A&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                ],
                [
                    "title" => "Over Stayed",
                    "value" => rand(10, 1000),
                    "icon" => "	fas fa-clock",
                    "color" => "l-bg-red-dark",
                    "link"  => env("BASE_URL") . "/api/daily?page=1&per_page=1000&company_id=$id&status=M&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                    "multi_in_out"  => env("BASE_URL") . "/api/multi_in_out_daily?page=1&per_page=1000&company_id=$id&status=M&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                ],
                [
                    "title" => "Total Visitor",
                    "value" => Visitor::whereCompanyId($id)->count(),
                    "icon" => "	fas fa-users",
                    "color" => "l-bg-cyan-dark",
                    "link"  => env("BASE_URL") . "/api/daily?page=1&per_page=1000&company_id=$id&status=M&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                    "multi_in_out"  => env("BASE_URL") . "/api/multi_in_out_daily?page=1&per_page=1000&company_id=$id&status=M&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                ],
            ],

            "hostCounts" => [
                [
                    "title" => "Total Company",
                    "value" => HostCompany::whereCompanyId($id)->count(),
                    "icon" => "fas fa-building",
                    "color" => "l-bg-green-dark",
                    "link"  => env("BASE_URL") . "/api/daily?company_id=$id&status=SA&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                    "multi_in_out"  => env("BASE_URL") . "/api/multi_in_out_daily?company_id=$id&status=SA&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                ],
                [
                    "title" => "Opened Office",
                    "value" => rand(10, 1000),
                    "icon" => "fas fa-door-open",
                    "color" => "l-bg-purple-dark",
                    "link"  => env("BASE_URL") . "/api/daily?page=1&per_page=1000&company_id=$id&status=P&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                    "multi_in_out"  => env("BASE_URL") . "/api/multi_in_out_daily?page=1&per_page=1000&company_id=$id&status=P&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                ],
                [
                    "title" => "Closed Office",
                    "value" => rand(10, 1000),
                    "icon" => "fas fa-door-closed",
                    "color" => "l-bg-orange-dark",
                    "link"  => env("BASE_URL") . "/api/daily?page=1&per_page=1000&company_id=$id&status=A&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                    "multi_in_out"  => env("BASE_URL") . "/api/multi_in_out_daily?page=1&per_page=1000&company_id=$id&status=A&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                ],
                [
                    "title" => "Weekend",
                    "value" => rand(10, 1000),
                    "icon" => "	fas fa-calendar",
                    "color" => "l-bg-red-dark",
                    "link"  => env("BASE_URL") . "/api/daily?page=1&per_page=1000&company_id=$id&status=M&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                    "multi_in_out"  => env("BASE_URL") . "/api/multi_in_out_daily?page=1&per_page=1000&company_id=$id&status=M&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                ],
                [
                    "title" => "Vacant",
                    "value" => rand(10, 1000),
                    "icon" => "	fas fa-users",
                    "color" => "l-bg-cyan-dark",
                    "link"  => env("BASE_URL") . "/api/daily?page=1&per_page=1000&company_id=$id&status=M&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                    "multi_in_out"  => env("BASE_URL") . "/api/multi_in_out_daily?page=1&per_page=1000&company_id=$id&status=M&daily_date=" . $date . "&department_id=-1&report_type=Daily",
                ],
            ],

            "statusCounts" => [
                [
                    "title" => "Approved",
                    "value" => $visitorCounts->sum('total_approved'),
                    "icon" => "fas fa-calendar-check",
                    "color" => "l-bg-green-dark",
                ],
                [
                    "title" => "Pending",
                    "value" => $visitorCounts->sum('total_pending'),
                    "icon" => "fas fa-clock",
                    "color" => "l-bg-orange-dark",
                ],
                [
                    "title" => "Rejected",
                    "value" => $visitorCounts->sum('total_rejected'),
                    "icon" => "	fas fa-clock",
                    "color" => "l-bg-purple-dark",
                ],
                [
                    "title" => "Cancelled",
                    "value" => $visitorCounts->sum('total_canceled'),
                    "icon" => "fas fa-calendar-times",
                    "color" => "l-bg-red-dark",
                ],
            ]
        ];
    }
}
