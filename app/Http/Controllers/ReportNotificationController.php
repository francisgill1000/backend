<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportNotification\StoreRequest;
use App\Http\Requests\ReportNotification\UpdateRequest;
use App\Models\ReportNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportNotificationController extends Controller
{
    public function index(ReportNotification $model, Request $request)
    {

        return $model->where('company_id', $request->company_id)
            ->when($request->filled('subject'), function ($q) use ($request) {
                $q->where('subject', 'ILIKE', "$request->subject%");
            })
            ->when($request->filled('frequency'), function ($q) use ($request) {
                $q->where('frequency', 'ILIKE', "$request->frequency%");
            })
            ->when($request->filled('time'), function ($q) use ($request) {
                $q->where('time', 'ILIKE', "$request->time%");
            })
            ->when($request->filled('serach_medium'), function ($q) use ($request) {
                $key = strtolower($request->serach_medium);
                //$q->where(DB::raw("json_contains('mediums', '$key')"));
                //$q->WhereJsonContains('mediums', $key);
                $q->WhereJsonContains(DB::raw('lower("mediums"::text)'), $key);
            })
            ->when($request->filled('serach_email_recipients'), function ($q) use ($request) {
                $key = strtolower($request->serach_email_recipients);
                $q->WhereJsonContains(DB::raw('lower("tos"::text)'), $key);
            })

            ->when($request->filled('sortBy'), function ($q) use ($request) {
                $sortDesc = $request->input('sortDesc');
                if (strpos($request->sortBy, '.')) {
                    // if ($request->sortBy == 'department.name.id') {
                    //     $q->orderBy(Department::select("name")->whereColumn("departments.id", "employees.department_id"), $sortDesc == 'true' ? 'desc' : 'asc');

                    // }

                } else {
                    $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc');{}

                }

            })

            ->paginate($request->per_page);
    }

    public function store(StoreRequest $request)
    {
        try {
            $record = ReportNotification::create($request->validated());

            if ($record) {
                return $this->response('Report Notification created.', $record, true);
            } else {
                return $this->response('Report Notification cannot create.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function show(ReportNotification $ReportNotification)
    {
        return $ReportNotification;
    }

    public function update(UpdateRequest $request, ReportNotification $ReportNotification)
    {
        try {
            $record = $ReportNotification->update($request->validated());

            if ($record) {
                return $this->response('Report Notification updated.', $record, true);
            } else {
                return $this->response('Report Notification update.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function destroy(ReportNotification $ReportNotification)
    {
        $record = $ReportNotification->delete();

        if ($record) {
            return $this->response('Report Notification deleted.', $record, true);
        } else {
            return $this->response('Report Notification cannot delete.', null, false);
        }
    }
}