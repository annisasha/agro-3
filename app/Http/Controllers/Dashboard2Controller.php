<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sensor;
use App\Models\Plant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class Dashboard2Controller extends Controller
{
    public function getTemperature()
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
            ->orderBy('read_date', 'DESC')
            ->first();

        Log::info('Temperature Data:', (array) $data);
        return $data;
    }

    public function getHumidity()
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
            ->orderBy('read_date', 'DESC')
            ->first();

        Log::info('Humidity Data:', (array) $data);
        return $data;
    }

    public function getWind()
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
            ->orderBy('read_date', 'DESC')
            ->first();

        Log::info('Wind Data:', (array) $data);
        return $data;
    }

    public function getLux()
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
            ->orderBy('read_date', 'DESC')
            ->first();

        Log::info('Lux Data:', (array) $data);
        return $data;
    }

    public function getRain()
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
            ->orderBy('read_date', 'DESC')
            ->first();

        Log::info('Rain Data:', (array) $data);
        return $data;
    }

    public function index(Request $request, string $siteId)
    {
        // $siteId = $request->input('site_id', 'SITE001'); 
        //         $temperatureData = $humidityData = $windData = $luxData = $rainData = null;
        //         $plants = [];

        if ($siteId === 'SITE001') {
            $temperatureData = $this->getTemperature();
            $humidityData = $this->getHumidity();
            $windData = $this->getWind();
            $luxData = $this->getLux();
            $rainData = $this->getRain();

            $plants = Plant::where('dev_id', 'TELU0100')->get()->map(function ($plant) {
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

        if ($siteId === 'SITE002') {
            return response()->json([]);
        }

        return response()->json(['message' => 'Site tidak ditemukan'], 404);
    }
}
    

    // public function index()
    // {
    //     $temperature = $this->getTemperature();
    //     $humidity = $this->getHumidity();
    //     $wind = $this->getWind();
    //     $lux = $this->getLux();
    //     $rain = $this->getRain();

    //     $temperatureData = [
    //         'read_value' => $temperature->read_value ?? null,
    //         'value_status' => $temperature->value_status ?? 'Data tidak tersedia'
    //     ];
    
    //     $humidityData = [
    //         'read_value' => $humidity->read_value ?? null,
    //         'value_status' => $humidity->value_status ?? 'Data tidak tersedia'
    //     ];
    
    //     $windData = [
    //         'read_value' => $wind->read_value ?? null,
    //         'value_status' => $wind->value_status ?? 'Data tidak tersedia'
    //     ];
    
    //     $luxData = [
    //         'read_value' => $lux->read_value ?? null,
    //         'value_status' => $lux->value_status ?? 'Data tidak tersedia'
    //     ];
    
    //     $rainData = [
    //         'read_value' => $rain->read_value ?? null,
    //         'value_status' => $rain->value_status ?? 'Data tidak tersedia'
    //     ];
        
    //     $plants = Plant::all();

    //     // Mengembalikan view dengan semua data
    //     return view('dashboard', compact(
    //         'temperatureData', 
    //         'humidityData', 
    //         'windData', 
    //         'luxData', 
    //         'rainData',
    //         'plants'
    //     ));
    // }
