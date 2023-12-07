<?php

namespace App\Http\Requests\HostCompany;

use Illuminate\Foundation\Http\FormRequest;

class Update extends FormRequest
{
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
        $validations = [];

        if ($this->logo) {
            $validations['logo'] = 'image|mimes:jpeg,png,jpg,gif|max:2048';
        }

        $validations['flat_number'] = 'required|string|max:255';
        $validations['floor_number'] = 'required|string|max:255';
        $validations['company_name'] = 'required|string|max:255';
        $validations['manager_name'] = 'required|string|max:255';
        $validations['number'] = 'required|string|max:255';
        $validations['emergency_phone'] = 'required|string|max:255';
        $validations['email'] = 'required|email|max:255';
        $validations['open_time'] = 'required';
        $validations['close_time'] = 'required';
        $validations["zone_id"] = "required|numeric";
        $validations['weekend'] = 'required';
        $validations['webaccess'] = 'required';
        $validations['address'] = 'nullable|string|max:255';
        $validations["company_id"] = "required";

        return $validations;
    }
}
