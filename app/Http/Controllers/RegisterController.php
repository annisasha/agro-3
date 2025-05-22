<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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

    // Ambil ID terakhir
    $lastUser = User::orderBy('user_id', 'desc')->first();
    if ($lastUser) {
        $lastNumber = intval(substr($lastUser->user_id, 4));
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $newNumber = '001';
    }
    $newUserId = 'USER' . $newNumber;

    $user = User::create([
        'user_id' => $newUserId,
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
