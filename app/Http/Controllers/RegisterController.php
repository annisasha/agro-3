<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_name' => 'required|string|unique:tm_user',
            'user_pass' => 'required',
            'user_email' => 'required|email|unique:tm_user',
            'user_phone' => 'required|string',
            'role_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'user_name' => $request->user_name,
            'user_pass' => Hash::make($request->user_pass),
            'user_email' => $request->user_email,
            'user_phone' => $request->user_phone,
            'user_sts' => '1',
            'role_id' => $request->role_id,
            'user_created' => now(),
            'user_updated' => now(),
        ]);

        return response()->json([
            'message' => 'Registrasi berhasil',
            'user' => $user,
        ]);
    }
}
