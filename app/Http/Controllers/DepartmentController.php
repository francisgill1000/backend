<?php

namespace App\Http\Controllers;

use App\Http\Requests\Department\DepartmentRequest;
use App\Http\Requests\Department\DepartmentUpdateRequest;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Department $department, Request $request)
    {
        return $department->filter($request)->paginate($request->per_page);
    }

    public function departmentEmployee(Request $request)
    {
        $model = Department::query();
        $model->where('company_id', $request->company_id);
        $model->with('employees:id,employee_id,system_user_id,first_name,last_name,display_name,department_id');
        $model->select("id", "name");
        return $model->paginate($request->per_page);
    }

    public function search(Request $request, $key)
    {
        $model = Department::query();
        $model->where('id', 'LIKE', "%$key%");
        $model->where('company_id', $request->company_id);
        $model->orWhere('name', 'LIKE', "%$key%");
        return $model->with('children')->paginate($request->per_page);
    }

    public function store(Department $model, DepartmentRequest $request)
    {
        $data = $request->validated();

        if ($request->company_id) {
            $data["company_id"] = $request->company_id;
        }
        try {

            $record = $model->create($data);
            $userCreated = (new UserController)->create_user($request);

            if ($record && $userCreated) {
                return $this->response('Department successfully added.', $record->with('children'), true);
            } else {
                return $this->response('Department cannot add.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function show(Department $Department)
    {
        return $Department->with('children');
    }

    public function update(DepartmentUpdateRequest $request, Department $Department)
    {
        try {
            $record = $Department->update($request->validated());

            if ($record) {
                return $this->response('Department successfully updated.', $Department->with('children'), true);
            } else {
                return $this->response('Department cannot update.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function destroy(Department $Department)
    {
        try {
            $record = $Department->delete();

            if ($record) {
                return $this->response('Department successfully deleted.', null, true);
            } else {
                return $this->response('Department cannot delete.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function deleteSelected(Department $model, Request $request)
    {
        try {
            $record = $model->whereIn('id', $request->ids)->delete();

            if ($record) {
                return $this->response('Department successfully deleted.', null, true);
            } else {
                return $this->response('Department cannot delete.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
