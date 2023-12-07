<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EmployeeDashboard extends Controller
{

    public function statistics(Request $request): array
    {

        $records = $this->getEmployeeAttendanceRecords($request);

        return [
            [
                'title' => 'Total Presents',
                'value' => $this->getStatusCount($records, 'P'),
                'icon' => 'fas fa-calendar-check',
                'color' => 'l-bg-green-dark',
                'link' => $this->getLink($request, 'P'),
            ],
            [
                'title' => 'Total Absence',
                'value' => $this->getStatusCount($records, 'A'),
                'icon' => 'fas fa-calendar-times',
                'color' => 'l-bg-orange-dark',
                'link' => $this->getLink($request, 'A'),
            ],
            [
                'title' => 'Total Missing',
                'value' => $this->getStatusCount($records, 'M'),
                'icon' => 'fas fa-calendar-times',
                'color' => 'l-bg-cyan-dark',
                'link' => $this->getLink($request, 'M'),
            ],
            [
                'title' => 'Total Leaves',
                'value' => $this->getStatusCount($records, 'L'),
                'icon' => 'fas fa-calendar-week',
                'color' => 'l-bg-orange-dark',
                'link' => $this->getLink($request, 'L'),
                'border_color' => '526C78',
            ],
            [
                'title' => 'Total Holidays',
                'value' => $this->getStatusCount($records, 'H'),
                'icon' => 'fas fa-calendar-plus',
                'color' => 'l-bg-blue-dark',
                'link' => $this->getLink($request, 'H'),
                'border_color' => '526C78',
            ],
            [
                'title' => 'Total Off',
                'value' => $this->getStatusCount($records, 'O'),
                'icon' => 'fas fa-calendar',
                'color' => 'l-bg-purple-dark',
                'link' => $this->getLink($request, 'O'),
                'border_color' => '526C78',
            ],
        ];
    }

    public function getLink($request, $status)
    {
        $baseUrl = env("BASE_URL");

        $params = [
            'main_shift_type' => $request->shift_type_id,
            'company_id' => $request->company_id,
            'status' => $status,
            'department_id' => $request->department_id,
            'employee_id' => $request->employee_id,
            'report_type' => 'Monthly',
            'from_date' => date("Y-m-01"),
            'to_date' => date("Y-m-t"),
        ];

        $queryString = http_build_query($params);

        //$url = $baseUrl . "/api/multi_in_out_daily?" . $queryString;
        $url = $baseUrl . "/api/multi_in_out_monthly?" . $queryString;

        return $url;
    }

    private function getStatusCount($records, $status): int
    {
        return $records->where('status', $status)->count();
    }

    public function getEmployeeAttendanceRecords($request)
    {
        $model = Attendance::query();

        $model->where('company_id', $request->company_id ?? 0);

        $model->where('employee_id', $request->employee_id);

        $model->whereMonth('date', now()->month);

        return $model->whereIn('status', ['P', 'A', 'M', 'O', 'H', 'L', 'V'])->get();

        // working code with cache
        $cacheKey = 'employee_attendance_records:' . $request->company_id . "_" . $request->employee_id;

        return Cache::remember($cacheKey, now()->endOfDay(), function () use ($request) {

            $model = Attendance::query();

            $model->where('company_id', $request->company_id ?? 0);

            $model->where('employee_id', $request->employee_id);

            $model->whereMonth('date', now()->month);

            return $model->whereIn('status', ['P', 'A', 'M', 'O', 'H', 'L', 'V'])->get();
        });
    }

    public function clearEmployeeCache($request)
    {
        $cacheKey = 'employee_attendance_records:' . $request->company_id . "_" . $request->employee_id;

        Cache::forget($cacheKey);
    }
}
