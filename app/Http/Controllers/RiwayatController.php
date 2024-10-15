<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sensor;

class RiwayatController extends Controller
{
    public function index(Request $request, string $areaId, string $siteId)
    {
        // $areaId = $request->input('id', 'AREA001');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (is_null($startDate) || is_null($endDate)) {
            return response()->json(['message' => 'Tanggal mulai dan tanggal akhir harus diisi.'], 400);
        }

        $startDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $startDate);
        $endDateTime = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $endDate);

        if (!$startDateTime || !$endDateTime) {
            return response()->json(['message' => 'Format tanggal tidak valid.'], 400);
        }

        if ($startDateTime->greaterThan($endDateTime)) {
            return response()->json(['message' => 'Tanggal mulai harus lebih kecil dari tanggal akhir.'], 400);
        }

        $validAreas = [];

        if ($siteId === 'SITE001') {
            $validAreas = ['AREA001', 'AREA002', 'AREA003']; 
        } else {
            return response()->json(['message' => 'Area tidak tersedia untuk site yang dipilih'], 404);
        }

        if (!in_array($areaId, $validAreas)) {
            return response()->json(['message' => 'Area tidak valid untuk site yang dipilih'], 404);
        }

        $results = [];

        if ($areaId === 'AREA001' || $areaId === 'AREA002' || $areaId === 'AREA003') {
            Sensor::whereIn('ds_id', ['temp', 'hum', 'ilum', 'wind', 'rain'])
                ->whereBetween('read_date', [$startDateTime, $endDateTime])
                ->chunk(100, function ($data) use (&$results) {
                    foreach ($data as $item) {
                        $results[] = [
                            'ds_id' => $item->ds_id,
                            'read_date' => $item->read_date,
                            'read_value' => $item->read_value,
                        ];
                    }
                });
        }

        if (empty($results)) {
            return response()->json(['message' => 'Tidak ada data untuk area atau rentang waktu yang dipilih'], 404);
        }

        return response()->json($results);
    }
}