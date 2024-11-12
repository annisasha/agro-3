<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plant;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $siteId = $request->input('site_id');

        if (empty($siteId)) {
            return response()->json(['message' => 'Pilih Site'], 400);
        }

        $devIds = DB::table('tm_device')
            ->where('site_id', $siteId)
            ->pluck('dev_id');

        if ($devIds->isEmpty()) {
            return response()->json(['message' => 'Site tidak ditemukan'], 404);
        }

        $temperatureData = $this->getTemperature($devIds);
        $humidityData = $this->getHumidity($devIds);
        $windData = $this->getWind($devIds);
        $luxData = $this->getLux($devIds);
        $rainData = $this->getRain($devIds);

        $plants = Plant::whereIn('dev_id', $devIds)->get()->map(function ($plant) {
            return [
                'pl_id' => $plant->pl_id,
                'pl_name' => $plant->pl_name,
                'pl_desc' => $plant->pl_desc,
                'pl_date_planting' => $plant->pl_date_planting,
                'age' => $plant->age(),
                'phase' => $plant->phase(),
                'timeto_harvest' => $plant->timetoHarvest(),
            ];
        });

        return response()->json([
            'site_id' => $siteId,
            'temperature' => $temperatureData,
            'humidity' => $humidityData,
            'wind' => $windData,
            'lux' => $luxData,
            'rain' => $rainData,
            'plants' => $plants,
        ]);
    }

    // Fungsi untuk mengambil data suhu
    public function getTemperature($devIds)
    {
        $data = DB::table('tm_sensor_read')
            ->selectRaw("
                read_value,
                CASE
                    WHEN read_value BETWEEN 18 AND 28 THEN 'OK'
                    WHEN read_value < 17 THEN 'Danger, Kurangi penyiraman malam hari untuk menghindari penurunan suhu'
                    WHEN read_value > 29 THEN 'Danger, Tambahkan irigasi untuk mengurangi efek panas'
                    ELSE 'Warning'
                END AS value_status
            ")
            ->where('ds_id', 'temp')
            ->whereIn('dev_id', $devIds)
            ->orderBy('read_date', 'DESC')
            ->first();

        return $data;
    }

    // Fungsi untuk mengambil data kelembapan
    public function getHumidity($devIds)
    {
        $data = DB::table('tm_sensor_read')
            ->selectRaw("
                read_value,
                CASE
                    WHEN read_value BETWEEN 40 AND 60 THEN 'OK'
                    WHEN read_value < 35 THEN 'Danger, Lakukan irigasi lebih sering'
                    WHEN read_value > 65 THEN 'Danger, Kurangi irigasi untuk mencegah kelembaban berlebihan'
                    ELSE 'Warning'
                END AS value_status
            ")
            ->where('ds_id', 'hum')
            ->whereIn('dev_id', $devIds)
            ->orderBy('read_date', 'DESC')
            ->first();

        return $data;
    }

    // Fungsi untuk mengambil data angin
    public function getWind($devIds)
    {
        $data = DB::table('tm_sensor_read')
            ->selectRaw("
                read_value,
                CASE
                    WHEN read_value BETWEEN 1 AND 15 THEN 'OK'
                    WHEN read_value > 16 THEN 'Danger, gunakan penahan angin seperti pagar tanaman'
                    ELSE 'Warning'
                END AS value_status
            ")
            ->where('ds_id', 'wind')
            ->whereIn('dev_id', $devIds)
            ->orderBy('read_date', 'DESC')
            ->first();

        return $data;
    }

    // Fungsi untuk mengambil data kecerahan
    public function getLux($devIds)
    {
        $data = DB::table('tm_sensor_read')
            ->selectRaw("
                read_value,
                CASE
                    WHEN read_value BETWEEN 10000 AND 50000 THEN 'OK'
                    WHEN read_value < 9999 THEN 'Danger, Pastikan tanaman menerima pencahayaan yang cukup, atau gunakan lampu tambahan jika diperlukan'
                    WHEN read_value > 50001 THEN 'Danger, Gunakan jaring peneduh untuk melindungi tanaman dari sinar matahari langsung'
                    ELSE 'Warning'
                END AS value_status
            ")
            ->where('ds_id', 'ilum')
            ->whereIn('dev_id', $devIds)
            ->orderBy('read_date', 'DESC')
            ->first();

        return $data;
    }

    // Fungsi untuk mengambil data curah hujan
    public function getRain($devIds)
    {
        $data = DB::table('tm_sensor_read')
            ->selectRaw("
                read_value,
                CASE
                    WHEN read_value BETWEEN 50 AND 150 THEN 'OK'
                    WHEN read_value < 45 THEN 'Danger, Lakukan irigasi tambahan untuk memenuhi kebutuhan air tanaman'
                    WHEN read_value > 155 THEN 'Danger, Perbaiki sistem drainase untuk menghindari genangan air di sawah'
                    ELSE 'Warning'
                END AS value_status
            ")
            ->where('ds_id', 'rain')
            ->whereIn('dev_id', $devIds)
            ->orderBy('read_date', 'DESC')
            ->first();

        return $data;
    }
}
