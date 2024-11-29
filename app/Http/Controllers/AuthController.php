<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'user_name' => 'required|string',
            'user_pass' => 'required|string',
        ]);

        $user = User::where('user_name', $request->user_name)->first();

        if ($user && Hash::check($request->user_pass, $user->user_pass)) {
            $token = $user->createToken('AgroFIT')->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil',
                'token' => $token, 
                'user' => $user,   
            ]);
        }

        return response()->json([
            'message' => 'Username atau password salah'
        ], 401);
    }
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }
}
