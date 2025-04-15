<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'user_name' => 'required|string',
            'user_pass' => 'required|string',
        ]);

        $user = User::where('user_name', $request->user_name)->first();

        if (!$user) {
            return response()->json(['message' => 'Username atau password salah'], 401);
        }

        // Log for debugging
        Log::info('Input password: ' . $request->user_pass);
        Log::info('Password in DB: ' . $user->user_pass);

        // Check the password without hashing
        if ($request->user_pass === $user->user_pass) {
            $token = $user->createToken('Coba')->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil',
                'token' => $token,
                'user' => $user->only(['user_id', 'user_name', 'user_email']), // Return only necessary user fields
            ]);
        }

        return response()->json(['message' => 'Username atau password salah'], 401);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Logout berhasil'
            ]);
        }

        return response()->json([
            'message' => 'Tidak ada pengguna yang login'
        ], 400);
    }
}
