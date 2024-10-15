<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function getTemperature()
    {
        $data = DB::table('tm_sensor_read')
            ->selectRaw("read_value")
            ->where('ds_id', 'temp')
            ->orderBy('read_date', 'DESC')
            ->first();

        Log::info('Temperature Data:', (array) $data);
        return $data;
    }

    public function getHumidity()
    {
        $data = DB::table('tm_sensor_read')
            ->selectRaw("read_value")
            ->where('ds_id', 'hum')
            ->orderBy('read_date', 'DESC')
            ->first();

        Log::info('Humidity Data:', (array) $data);
        return $data;
    }

    public function getWind()
    {
        $data = DB::table('tm_sensor_read')
            ->selectRaw("read_value")
            ->where('ds_id', 'wind')
            ->orderBy('read_date', 'DESC')
            ->first();

        Log::info('Wind Data:', (array) $data);
        return $data;
    }

    public function getLux()
    {
        $data = DB::table('tm_sensor_read')
            ->selectRaw("read_value")
            ->where('ds_id', 'ilum')
            ->orderBy('read_date', 'DESC')
            ->first();

        Log::info('Lux Data:', (array) $data);
        return $data;
    }

    public function getRain()
    {
        $data = DB::table('tm_sensor_read')
            ->selectRaw("read_value")
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
