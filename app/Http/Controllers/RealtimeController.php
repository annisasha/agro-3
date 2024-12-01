<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RealtimeController extends Controller
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

        $nitrogenData = $this->getNitrogen($devIds);
        $fosforData = $this->getFosfor($devIds);
        $kaliumData = $this->getKalium($devIds);
        $tdsData = $this->getTDS($devIds);
        $ecData = $this->getEC($devIds);
        $soilhumData = $this->getSoilHum($devIds);
        $soilphData = $this->getSoilPh($devIds);
        $soiltempData = $this->getSoilTemp($devIds);
        $lastUpdated = $this->getLastUpdatedDate($devIds);

        return response()->json(
            [
                'site_id' => $siteId,
                'nitrogen' => $nitrogenData,
                'fosfor' => $fosforData,
                'kalium' => $kaliumData,
                'tds' => $tdsData,
                'ec' => $ecData,
                'soil_hum' => $soilhumData,
                'soil_ph' => $soilphData,
                'soil_temp' => $soiltempData,
                'last_updated' => $lastUpdated
            ]
        );
    }

    private function getLastUpdatedDate($devIds)
    {
        $latestReadDate = DB::table('tm_sensor_read')
            ->whereIn('dev_id', $devIds)
            ->max('read_date');  

        return $latestReadDate ? \Carbon\Carbon::parse($latestReadDate)->format('d-m-Y H:i') : null;
    }

    // Fungsi untuk mengambil batas atas batas bawah dari sensor
    private function getSensorThresholds($ds_id)
    {
        $sensorThresholds = DB::table('td_device_sensors')
            ->where('ds_id', $ds_id)
            ->select('ds_min_norm_value', 'ds_max_norm_value', 'min_danger_action', 'max_danger_action')
            ->first();

        if (!$sensorThresholds) {
            Log::error("No thresholds found for sensor ID: $ds_id");
            return null;
        }

        return $sensorThresholds;
    }

    public function getSensorName($ds_id)
    {
        return DB::table('td_device_sensors')
            ->where('ds_id', $ds_id)
            ->value('ds_name');
    }

private function getSensorData($devIds, $sensors, $sensorType, $valueModifier = 1)
{
    $data = [];

    foreach ($sensors as $sensor) {
        $sensorData = DB::table('tm_sensor_read')
            ->select('ds_id', 'read_value', 'read_date')
            ->where('ds_id', $sensor)
            ->whereIn('dev_id', $devIds)
            ->orderBy('read_date', 'DESC')
            ->first();

        $sensorLimits = $this->getSensorThresholds($sensor);

        if (!$sensorLimits) {
            Log::warning("No thresholds found for sensor: $sensor");
            continue;
        }

        $minValue = $sensorLimits->ds_min_norm_value * $valueModifier;
        $maxValue = $sensorLimits->ds_max_norm_value * $valueModifier;
        $minDangerAct = $sensorLimits->min_danger_action;
        $maxDangerAct = $sensorLimits->max_danger_action;

        $valueStatus = '';
        $actionMessage = '';
        $statusMessage = '';
        $sensorName = $this->getSensorName($sensor);

        if ($sensorData) {
            $readValue = $sensorData->read_value * $valueModifier;

            if ($readValue >= $minValue && $readValue <= $maxValue) {
                $valueStatus = 'OK';
                $statusMessage = "$sensorType dalam kondisi normal";
            } elseif ($readValue < $minValue) {
                $valueStatus = 'Danger';
                $statusMessage = "$sensorType di bawah batas normal";
                $actionMessage = $minDangerAct;
            } elseif ($readValue > $maxValue) {
                $valueStatus = 'Danger';
                $statusMessage = "$sensorType di atas batas normal";
                $actionMessage = $maxDangerAct;
            } else {
                $valueStatus = 'Warning';
                $statusMessage = "$sensorType mendekati ambang batas";
                $actionMessage = "Periksa kondisi lebih lanjut untuk $sensorType.";
            }

            $data[] = [
                'sensor' => $sensor,
                'read_value' => $readValue,
                'read_date' => $sensorData->read_date ?? null,
                'value_status' => $valueStatus,
                'status_message' => $statusMessage,
                'action_message' => $actionMessage,
                'sensor_name' => $sensorName
            ];
        }
    }

    return $data;
}

public function getNitrogen($devIds)
{
    $sensors = ['soil_nitro1', 'soil_nitro2', 'soil_nitro3', 'soil_nitro5', 'soil_nitro6'];
    return $this->getSensorData($devIds, $sensors, 'Nitrogen');
}

public function getFosfor($devIds)
{
    $sensors = ['soil_phos1', 'soil_phos2', 'soil_phos3', 'soil_phos5', 'soil_phos6'];
    return $this->getSensorData($devIds, $sensors, 'Fosfor', 0.1);
}

public function getKalium($devIds)
{
    $sensors = ['soil_pot1', 'soil_pot2', 'soil_pot3', 'soil_pot5', 'soil_pot6'];
    return $this->getSensorData($devIds, $sensors, 'Kalium');
}

public function getTDS($devIds)
{
    $sensors = ['soil_tds1', 'soil_tds2', 'soil_tds3', 'soil_tds5', 'soil_tds6'];
    return $this->getSensorData($devIds, $sensors, 'TDS');
}

public function getEC($devIds)
{
    $sensors = ['soil_con1', 'soil_con2', 'soil_con3', 'soil_con5', 'soil_con6'];
    return $this->getSensorData($devIds, $sensors, 'EC', 0.01);
}

public function getSoilHum($devIds)
{
    $sensors = ['soil_hum1', 'soil_hum2', 'soil_hum3', 'soil_hum5', 'soil_hum6'];
    return $this->getSensorData($devIds, $sensors, 'Kelembapan tanah');
}

public function getSoilPh($devIds)
{
    $sensors = ['soil_ph1', 'soil_ph2', 'soil_ph3', 'soil_ph5', 'soil_ph6'];
    return $this->getSensorData($devIds, $sensors, 'pH tanah', 0.1);
}
public function getSoilTemp($devIds)
{
    $sensors = ['soil_temp1', 'soil_temp2', 'soil_temp3', 'soil_temp5', 'soil_temp6'];
    return $this->getSensorData($devIds, $sensors, 'Suhu tanah');
}
}