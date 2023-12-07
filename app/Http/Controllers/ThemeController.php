<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ThemeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // return Theme::truncate();
        // return Theme::count();

        $id = $request->company_id;

        $counts = $this->getCounts($request->company_id ?? 8);

        $jsonColumn = Theme::where("company_id", $id)
            ->where("page", $request->page)
            ->where("type", $request->type)
            ->value("style") ?? [];

        foreach ($jsonColumn as &$card) {
            $card["calculated_value"] = str_pad($counts[$card["value"]] ?? "", 2, '0', STR_PAD_LEFT);
        }
        return $jsonColumn;
    }

    public function getCounts($id = 0): array
    {
        $model = Attendance::where('company_id', $id)
            ->whereIn('status', ['P', 'A', 'M', 'O', 'H', 'L', 'V'])
            ->whereDate('date', date("Y-m-d"))
            ->select('status')
            ->get();

        $attendanceCounts = AttendanceLog::where("company_id", $id)
            ->whereDate("LogTime", date("Y-m-d"))
            ->groupBy("UserID")
            ->selectRaw('"UserID", COUNT(*) as count')
            ->get();

        $countsByParity = $attendanceCounts->groupBy(fn ($item) => $item->count % 2 === 0 ? 'even' : 'odd')->map->count();

        return [
            'totalIn' => $countsByParity->get('odd', 0),
            'totalOut' => $countsByParity->get('even', 0),
            "employeeCount" => Employee::where("company_id", $id)->count() ?? 0,
            "presentCount" => $model->where('status', 'P')->count(),
            "absentCount" => $model->where('status', 'A')->count(),
            "missingCount" => $model->where('status', 'M')->count(),
            "offCount" => $model->where('status', 'O')->count(),
            "holidayCount" => $model->where('status', 'H')->count(),
            "leaveCount" => $model->where('status', 'L')->count(),
            "vaccationCount" => $model->where('status', 'V')->count(),
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Theme::where("company_id", $request->company_id)->where("page", $request->page)->where("type", $request->type)->delete();

        return Theme::create([
            "page" => $request->page,
            "type" => $request->type,
            "style" => $request->style,
            "company_id" => $request->company_id
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Theme  $theme
     * @return \Illuminate\Http\Response
     */
    public function theme_count(Request $request)
    {
        return $counts = $this->getCounts($request->company_id);
        return str_pad($counts[$request->value] ?? "", 2, '0', STR_PAD_LEFT);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Theme  $theme
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Theme $theme)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Theme  $theme
     * @return \Illuminate\Http\Response
     */
    public function destroy(Theme $theme)
    {
        //
    }
}
