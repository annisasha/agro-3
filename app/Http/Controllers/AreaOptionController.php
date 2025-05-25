<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AreaOptionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $siteId = $request->input('site_id');

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!$siteId) {
            return response()->json(['message' => 'Parameter site_id diperlukan'], 400);
        }

        // Ambil semua ds_id sensor aktif milik user di site yang dipilih
        $dsIds = DB::table('td_device_sensors')
            ->join('tm_device', 'tm_device.dev_id', '=', 'td_device_sensors.dev_id')
            ->where('tm_device.site_id', $siteId)
            ->where('tm_device.user_id', $user->user_id)
            ->where('td_device_sensors.ds_sts', 1)
            ->pluck('td_device_sensors.ds_id');

        $areaSet = collect();

        foreach ($dsIds as $dsId) {
            // Lingkungan → sensor seperti env_temp, env_hum, dll
            if (str_starts_with($dsId, 'env_')) {
                $areaSet->push('env');
            }

            // Area → ambil angka di akhir seperti soil_ph2
            if (preg_match('/(\d+)$/', $dsId, $matches)) {
                $areaSet->push($matches[1]);
            }
        }

        // Hapus duplikat
        $uniqueAreas = $areaSet->unique()->values();

        // Mapping ke bentuk { value, label }
        $formatted = $uniqueAreas->map(function ($area) {
            return [
                'value' => $area,
                'label' => $area === 'env' ? 'Lingkungan' : 'Area ' . $area,
            ];
        });

        return response()->json([
            'areas' => $formatted
        ]);
    }
}
