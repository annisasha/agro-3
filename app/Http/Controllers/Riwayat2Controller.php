<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Riwayat2Controller extends Controller
{
    public function index(Request $request)
    {
        $siteId = $request->input('site_id'); 

        $selectedSensors = $request->input('sensors', []);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $plantDate = '2024-08-08'; 

        if (empty($startDate) || empty($endDate)) {
            return response()->json(['message' => 'Tanggal mulai dan akhir harus diisi.'], 400);
        }

        // Ngambil semua sensor yang ada di site
        $siteSensors = DB::table('td_device_sensor')
            ->join('tm_device', 'tm_device.dev_id', '=', 'td_device_sensor.dev_id')
            ->where('tm_device.site_id', $siteId)
            ->pluck('td_device_sensor.ds_id');

        if ($siteSensors->isEmpty()) {
            return response()->json(['message' => 'Tidak ada sensor yang terhubung dengan site ini.'], 404);
        }

        // Jika mau menampilkan data semua sensor
        if (in_array('all', $selectedSensors)) {
            $selectedSensors = $siteSensors->toArray();
        }

        $data = DB::table('tm_sensor_read')
            ->select('ds_id', DB::raw('MIN(read_date) as read_date'), DB::raw('MAX(read_value) as read_value'))
            ->whereIn('ds_id', $selectedSensors)
            ->whereBetween('read_date', [$startDate, $endDate])
            ->whereRaw("TIME(read_date) BETWEEN '07:00:00' AND '07:59:59'")
            // Kondisi untuk mengambil data dari hari ke-1 dengan kelipatan 7
            ->whereRaw("DATE(read_date) >= DATE_ADD(?, INTERVAL 1 DAY)", [$plantDate])
            ->whereRaw("MOD(DATEDIFF(DATE(read_date), ?), 7) = 1", [$plantDate])
            ->groupBy(DB::raw('DATE(read_date), ds_id'))
            ->orderBy('read_date', 'ASC')
            ->get();

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data untuk rentang waktu yang dipilih.'], 404);
        }

        $result = $data->map(function ($item) {
            $sensorName = DB::table('td_device_sensor')
                ->where('ds_id', $item->ds_id)
                ->value('ds_name');

            return [
                'ds_id' => $item->ds_id,
                'sensor_name' => $sensorName ?? $item->ds_id,
                'read_date' => $item->read_date,
                'read_value' => $item->read_value,
            ];
        });

        return response()->json($result);
    }
}
