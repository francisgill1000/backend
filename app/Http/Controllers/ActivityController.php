<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        return $this->filters($request)->orderByDesc("id")->paginate($request->per_page ?? 10);
    }

    public function show(Request $request, $user_id)
    {
        return $this->filters($request)->where("user_id", $user_id)->orderByDesc("id")->first();
    }

    public function activitiesByUser(Request $request, $user_id)
    {
        return $this->filters($request)->where("user_id", $user_id)->orderByDesc("id")->get();
    }

    public function filters($request)
    {
        $model = Activity::query();

        $model->when($request->filled("action"), function ($q) use ($request) {
            return $q->where("action", $request->action);
        });
        $model->when($request->filled("type"), function ($q) use ($request) {
            return $q->where("type", $request->type);
        });
        return $model->with('employee');
    }

    public function store(Request $request)
    {
        try {
            $record = Activity::create($request->all());

            if ($record) {
                return $this->response('Activity Successfully created.', $record, true);
            } else {
                return $this->response('Activity cannot create.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
