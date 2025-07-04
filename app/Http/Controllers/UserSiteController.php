<?php

// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;

// class UserSiteController extends Controller
// {
//     public function index(Request $request)
//     {
//         $user = $request->user();

//         if (!$user) {
//             return response()->json(['message' => 'Unauthorized'], 401);
//         }

//         Log::info('🔐 Mencari site milik user:', [
//             'user_id' => $user->user_id,
//             'user_name' => $user->user_name
//         ]);

//         // Coba cari berdasarkan user_id (UUID)
//         $sites = DB::table('tm_device')
//             ->join('tm_site', 'tm_device.site_id', '=', 'tm_site.site_id')
//             ->where('tm_device.user_id', $user->user_id)
//             ->select('tm_device.site_id', 'tm_site.site_name', 'tm_site.site_address', 'tm_site.site_lon', 'tm_site.site_lat', 'tm_site.site_elevasi', 'tm_site.site_sts', 'tm_site.site_created', 'tm_site.site_update')
//             ->distinct()
//             ->get();

//         // Jika tidak ketemu, fallback ke user_name
//         if ($sites->isEmpty()) {
//             Log::info('🔁 Fallback ke user_name karena user_id tidak ditemukan di device.');

//             $sites = DB::table('tm_device')
//                 ->join('tm_site', 'tm_device.site_id', '=', 'tm_site.site_id')
//                 ->where('tm_device.user_id', $user->user_name)
//                 ->select('tm_device.site_id', 'tm_site.site_name', 'tm_site.site_address', 'tm_site.site_lon', 'tm_site.site_lat', 'tm_site.site_elevasi', 'tm_site.site_sts', 'tm_site.site_created', 'tm_site.site_update')
//                 ->distinct()
//                 ->get();
//         }

//         if ($sites->isEmpty()) {
//             return response()->json(['message' => 'Tidak ada lahan yang terdaftar di akun ini'], 404);
//         }

//         return response()->json($sites);
//     }
// }



namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class UserSiteController extends Controller
{
    // READ - Get all sites
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
            ->select('tm_device.site_id', 'tm_site.site_name', 'tm_site.site_address', 'tm_site.site_lon', 'tm_site.site_lat', 'tm_site.site_elevasi', 'tm_site.site_sts', 'tm_site.site_created', 'tm_site.site_update')
            ->distinct()
            ->get();

        // Jika tidak ketemu, fallback ke user_name
        if ($sites->isEmpty()) {
            Log::info('🔁 Fallback ke user_name karena user_id tidak ditemukan di device.');

            $sites = DB::table('tm_device')
                ->join('tm_site', 'tm_device.site_id', '=', 'tm_site.site_id')
                ->where('tm_device.user_id', $user->user_name)
                ->select('tm_device.site_id', 'tm_site.site_name', 'tm_site.site_address', 'tm_site.site_lon', 'tm_site.site_lat', 'tm_site.site_elevasi', 'tm_site.site_sts', 'tm_site.site_created', 'tm_site.site_update')
                ->distinct()
                ->get();
        }

        if ($sites->isEmpty()) {
            return response()->json(['message' => 'Tidak ada lahan yang terdaftar di akun ini'], 404);
        }

        return response()->json(['data' => $sites]);
    }

    // CREATE - Create a new site
    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'site_address' => 'nullable|string',
            'site_lat' => 'nullable|numeric',
            'site_lon' => 'nullable|numeric',
            'site_elevasi' => 'nullable|numeric',
            'site_sts' => 'nullable|integer'
        ]);

        $siteId = (string) Str::uuid();

        DB::table('tm_site')->insert([
            'site_id' => $siteId,
            'site_name' => $validated['site_name'],
            'site_address' => $validated['site_address'] ?? null,
            'site_lat' => $validated['site_lat'] ?? null,
            'site_lon' => $validated['site_lon'] ?? null,
            'site_elevasi' => $validated['site_elevasi'] ?? null,
            'site_sts' => $validated['site_sts'] ?? 1,
            'site_created' => Carbon::now(),
            'site_update' => Carbon::now()
        ]);

        return response()->json(['message' => 'Site created successfully', 'site_id' => $siteId], 201);
    }

    // READ - Get a single site by ID
    public function show($id)
    {
        $site = DB::table('tm_site')->where('site_id', $id)->first();

        if (!$site) {
            return response()->json(['message' => 'Site not found'], 404);
        }

        return response()->json(['data' => $site]);
    }

    // UPDATE - Update a site by ID
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'site_name' => 'sometimes|string|max:255',
            'site_address' => 'nullable|string',
            'site_lat' => 'nullable|numeric',
            'site_lon' => 'nullable|numeric',
            'site_elevasi' => 'nullable|numeric',
            'site_sts' => 'nullable|integer'
        ]);

        $affected = DB::table('tm_site')
            ->where('site_id', $id)
            ->update(array_merge($validated, ['site_update' => Carbon::now()]));

        if ($affected === 0) {
            return response()->json(['message' => 'No changes made or site not found'], 404);
        }

        return response()->json(['message' => 'Site updated successfully']);
    }

    // DELETE - Delete a site by ID
    public function destroy($id)
    {
        $deleted = DB::table('tm_site')->where('site_id', $id)->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Site not found'], 404);
        }

        return response()->json(['message' => 'Site deleted successfully']);
    }
}
