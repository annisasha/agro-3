<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $siteId = $request->input('site_id'); 
        $areas = $request->input('areas', []); 
        $selectedSensors = $request->input('sensors', []); 
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (empty($startDate) || empty($endDate)) {
            return response()->json(['message' => 'Tanggal mulai dan akhir harus diisi.'], 400);
        }

        if (empty($areas)) {
            return response()->json(['message' => 'Area harus diisi.'], 400);
        }

        $areaMapping = [
            1 => ['soil1_ph', 'soil1_temp', 'soil1_nitro', 'soil1_phos', 'soil1_pot', 'soil1_hum', 'soil1_tds', 'soil1_con'],
            2 => ['soil2_ph', 'soil2_temp', 'soil2_nitro', 'soil2_phos', 'soil2_pot', 'soil2_hum', 'soil2_tds', 'soil2_con'],
            "lingkungan" => ['env_temp', 'env_hum']
        ];

        $allowedSensors = [];
        if (in_array('all', $areas)) {
            foreach ($areaMapping as $sensors) {
                $allowedSensors = array_merge($allowedSensors, $sensors);
            }
        } else {
            foreach ($areas as $area) {
                $allowedSensors = array_merge($allowedSensors, $areaMapping[$area] ?? []);
            }
        }

        $siteSensors = DB::table('td_device_sensor')
            ->join('tm_device', 'tm_device.dev_id', '=', 'td_device_sensor.dev_id')
            ->where('tm_device.site_id', $siteId)
            ->where('tm_device.dev_id', 'TELU0300')
            ->pluck('td_device_sensor.ds_id')
            ->toArray();

        $filteredSensors = array_intersect($siteSensors, $allowedSensors);

        if (!empty($selectedSensors) && !in_array('all', $selectedSensors)) {
            $filteredSensors = array_intersect($filteredSensors, $selectedSensors);
        }

        if (empty($filteredSensors)) {
            return response()->json(['message' => 'Tidak ada sensor yang valid untuk site dan area ini.'], 404);
        }

        $data = DB::table('tm_sensor_read')
            ->select(
                'ds_id',
                DB::raw('DATE(read_date) as read_date'),
                DB::raw('MIN(TIME(read_date)) as read_time'),
                DB::raw('MAX(read_value) as read_value')
            )
            ->whereIn('ds_id', $filteredSensors)
            ->whereBetween('read_date', [$startDate, $endDate])
            ->whereRaw("TIME(read_date) BETWEEN '07:00:00' AND '07:59:59'")
            ->groupBy('ds_id', DB::raw('DATE(read_date)'))
            ->orderBy('read_date', 'ASC')
            ->orderBy('ds_id', 'ASC')
            ->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data untuk rentang waktu yang dipilih.'], 404);
        }

        return response()->json($data, 200, [], JSON_PRETTY_PRINT);;
    }
}
