<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sensor;
use App\Models\Site;
use App\Models\Area;
use Illuminate\Support\Facades\Log;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $areaId = $request->input('area_id');
        $siteId = $request->input('site_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (is_null($startDate) || is_null($endDate)) {
            return response()->json(['message' => 'Tanggal mulai dan tanggal akhir harus diisi.'], 400);
        }

        try {
            $startDateTime = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $startDate);
            $endDateTime = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $endDate);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Format tanggal tidak valid.'], 400);
        }

        if ($startDateTime->greaterThan($endDateTime)) {
            return response()->json(['message' => 'Tanggal mulai harus lebih kecil dari tanggal akhir.'], 400);
        }

        // Mengecek apakah site_id valid
        $site = Site::find($siteId);
        if (!$site) {
            return response()->json(['message' => 'Site tidak ditemukan.'], 404);
        }

        $validAreas = Area::where('site_id', $siteId)->pluck('id')->toArray();

        if (empty($validAreas)) {
            Log::error('Area tidak ditemukan untuk Site ini');
            return response()->json(['message' => 'Area tidak ditemukan untuk Site ini'], 404);
        }

        // Menyaring data sensor berdasarkan areaId dan rentang waktu yang dipilih
        $results = Sensor::whereIn('ds_id', ['temp', 'hum', 'ilum', 'wind', 'rain'])
            ->whereBetween('read_date', [$startDateTime, $endDateTime])
            ->whereHas('device', function ($query) use ($siteId) {
                $query->where('site_id', $siteId);
            })
            ->get(['ds_id', 'read_date', 'read_value']);

        if ($results->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data untuk area atau rentang waktu yang dipilih'], 404);
        }

        return response()->json($results);
    }
}
