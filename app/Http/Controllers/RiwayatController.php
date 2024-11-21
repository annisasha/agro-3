<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sensor;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $sensorType = $request->input('ds_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Validasi tanggal
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

        $sensors = Sensor::distinct()->pluck('ds_id')->toArray();

        if ($sensorType && !in_array($sensorType, $sensors)) {
            return response()->json(['message' => 'Jenis sensor tidak valid.'], 400);
        }

        $sensorQuery = Sensor::query()
            ->whereBetween('read_date', [$startDateTime, $endDateTime]);

        if ($sensorType) {
            $sensorQuery->where('ds_id', $sensorType);
        }

        $results = $sensorQuery->get(['ds_id', 'read_date', 'read_value']);

        if ($results->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data untuk rentang waktu yang dipilih'], 404);
        }

        return response()->json($results);
    }

    public function listSensors()
    {
        $sensors = Sensor::distinct()->pluck('ds_id');
        return response()->json($sensors);
    }
}
