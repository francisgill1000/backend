<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->except("password_confirmation");
        $data["name"] = "ignore";
        $data["email"] = date("Y-m-d H:i:s");
        return User::create($data);
    }
}
