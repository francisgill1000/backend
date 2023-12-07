<?php

namespace App\Http\Requests\Employee;

use App\Traits\failedValidationWithName;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    use failedValidationWithName;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'company_id' => ['required'],
            'employee_id' => ['required'],
            'system_user_id' => ['required', 'regex:/^[1-9][0-9]*$/'],
            'display_name' => ['required', 'min:3', 'max:10'],
            'first_name' => ['required', 'min:3', 'max:10'],
            'last_name' => ['required', 'min:3', 'max:10'],
            'title' => ['required'],
            'status' => ['nullable'],
            'department_id' => ['nullable'],
            'sub_department_id' => ['nullable'],
            'designation_id' => ['nullable'],
            'employee_id' => ['required'],
            'leave_group_id' => ['nullable'],
            'reporting_manager_id' => ['nullable'],

            'profile_picture' => ['image', 'mimes:jpeg,png,jpg,svg', 'max:2048', 'sometimes', 'nullable'],
        ];
    }

    public function messages()
    {
        return [
            'system_user_id.regex' => 'The employee device ID should not start with zero.',
        ];
    }
}
