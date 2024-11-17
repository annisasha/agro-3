<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'user_name' => 'required|string',
            'user_pass' => 'required|string',
        ]);

        $user = User::where('user_name', $request->user_name)->first();

        if ($user && Hash::check($request->user_pass, $user->user_pass)) {
            $token = $user->createToken('YourAppName')->plainTextToken;
            Auth::login($user); 
            return response()->json([
                'message' => 'Login berhasil',
                'user' => $user,
            ]);
        }

        return response()->json(['message' => 'Username atau password salah'], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logout berhasil',
        ]);
    }
}
