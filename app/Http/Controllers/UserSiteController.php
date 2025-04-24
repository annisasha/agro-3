<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserSiteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        Log::info('🔐 Mencari site milik user:', [
            'user_id' => $user->user_id,
            'user_name' => $user->user_name
        ]);

        // Coba cari berdasarkan user_id (UUID)
        $sites = DB::table('tm_device')
            ->join('tm_site', 'tm_device.site_id', '=', 'tm_site.site_id')
            ->where('tm_device.user_id', $user->user_id)
            ->select('tm_device.site_id', 'tm_site.site_name')
            ->distinct()
            ->get();

        // Jika tidak ketemu, fallback ke user_name
        if ($sites->isEmpty()) {
            Log::info('🔁 Fallback ke user_name karena user_id tidak ditemukan di device.');

            $sites = DB::table('tm_device')
                ->join('tm_site', 'tm_device.site_id', '=', 'tm_site.site_id')
                ->where('tm_device.user_id', $user->user_name)
                ->select('tm_device.site_id', 'tm_site.site_name')
                ->distinct()
                ->get();
        }

        if ($sites->isEmpty()) {
            return response()->json(['message' => 'User tidak memiliki site'], 404);
        }

        return response()->json($sites);
    }
}
