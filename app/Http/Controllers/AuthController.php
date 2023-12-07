<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AssignedDepartmentEmployee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            // Check database connection
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'email' => ['Database is down'],
            ]);
        }

        $user = User::where('email', $request->email)
            ->with("company:id,user_id,name,location,logo,company_code,expiry")
            ->select(
                // "id",
                // "email",
                // "password",
                // "is_master",
                // "role_id",
                // "company_id",
                // "employee_role_id",
                // "can_login",
                // "web_login_access",
                // "mobile_app_login_access",
            )
            ->first();

        $this->throwErrorIfFail($request, $user);

        // @params User Id, action,type,companyId.
        // $this->recordActivity($user->id, "Login", "Authentication", $user->company_id);

        $user->user_type = $this->getUserType($user);

        unset($user->company);
        unset($user->employee);

        $arr = [
            'token' => $user->createToken('myApp')->plainTextToken,
            'user' => $user,
        ];

        return $arr;
    }

    public function me(Request $request)
    {
        // return User::where("id", ">", 0)->update(["employee_role_id" => 0]);

        // $data = User::withOut("assigned_permissions")->where("employee_role_id", ">", 0)->get(["id", "employee_role_id", "role_id"]);

        // foreach ($data as $key => $value) {
        //     User::where("id", $value->id)->update(["role_id" => $value->employee_role_id]);
        // }

        // return $data;

        $user = $request->user();
        $user->load("company");
        $user->user_type = $this->getUserType($user);
        $user->permissions = $user->assigned_permissions ? $user->assigned_permissions->permission_names : [];
        return ['user' => $user];
    }

    public function getUserType($user)
    {

        if ($user->company_id > 0) {

            if ($user->role_id == 0) {
                return "company";
            }

            $assginedDepartments = $this->getAssignedDepartments($user);

            if (count($assginedDepartments) == 0) {
                return "employee";
            }

            $user->assignedDepartments = $this->getAssignedDepartments($user);

            return "manager";
        } else {
            return $user->role_id > 0 ? "user" : "master";
        }
    }

    public function getAssignedDepartments($user)
    {
        return (new AssignedDepartmentEmployee)->assginedDepartment($user->employee->id ?? 0)->pluck("id");
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
    }

    public function throwErrorIfFail($request, $user)
    {
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        } else if ($user->company_id > 0 && $user->company->expiry < now()) {
            throw ValidationException::withMessages([
                'email' => ['Subscription has been expired.'],
            ]);
        } else if (!$user->web_login_access && !$user->is_master) {
            throw ValidationException::withMessages([
                'email' => ['Login access is not available. Please contact your admin.'],
            ]);
        }
    }
}
