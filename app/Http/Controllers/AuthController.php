<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where(['number' => $request->number])->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return 'The provided credentials are incorrect.';
        }

        return $user->createToken('token')->plainTextToken;
    }

    public function me()
    {
        return ["user" => Auth::user()];
    }

    public function logout()
    {
        return Auth::user()->tokens()->delete();
    }
}
