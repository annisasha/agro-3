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
            1 => ['soil_ph1', 'soil_temp1', 'soil_nitro1', 'soil_phos1', 'soil_pot1', 'soil_hum1', 'soil_tds1', 'soil_con1'],
            2 => ['soil_ph2', 'soil_temp2', 'soil_nitro2', 'soil_phos2', 'soil_pot2', 'soil_hum2', 'soil_tds2', 'soil_con2'],
            3 => ['soil_ph3', 'soil_temp3', 'soil_nitro3', 'soil_phos3', 'soil_pot3', 'soil_hum3', 'soil_tds3', 'soil_con3'],
            4 => ['soil_ph5', 'soil_temp5', 'soil_nitro5', 'soil_phos5', 'soil_pot5', 'soil_hum5', 'soil_tds5', 'soil_con5'],
            5 => ['soil_ph6', 'soil_temp6', 'soil_nitro6', 'soil_phos6', 'soil_pot6', 'soil_hum6', 'soil_tds6', 'soil_con6']
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
