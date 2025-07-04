<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RealtimeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $siteId = $request->input('site_id');

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (empty($siteId)) {
            return response()->json(['message' => 'Pilih Site'], 400);
        }

        // 🔐 Cek apakah site_id ini milik user
        $isAuthorized = DB::table('tm_device')
            ->where('site_id', $siteId)
            ->where('user_id', $user->user_id)
            ->exists();

        if (!$isAuthorized) {
            return response()->json(['message' => 'Lahan ini tidak terdaftar sebagai milik anda'], 403);
        }

        $devIds = DB::table('tm_device')
            ->where('site_id', $siteId)
            ->where('user_id', $user->user_id)
            ->pluck('dev_id');

        if ($devIds->isEmpty()) {
            return response()->json(['message' => 'Tidak ada alat sensor yang terdaftar ke lahan ini'], 404);
        }

        $activeSensors = DB::table('td_device_sensors')
            ->where('ds_sts', 1)
            ->where('ds_id', 'LIKE', 'soil_%')
            ->pluck('ds_id')
            ->filter(function ($id) {
                return preg_match('/\d+$/', $id); // hanya ds_id yang diakhiri angka
            })
            ->values(); // reset indeks

        if ($activeSensors->isEmpty()) {
            return response()->json(['message' => 'Tidak ada sensor aktif'], 404);
        }

        $sensorData = $this->getSensorData($devIds, $activeSensors);
        $lastUpdated = $this->getLastUpdatedDate($devIds);

        if (empty($sensorData)) {
            return response()->json([
                'message' => 'Tidak ada data sensor untuk lahan ini',
                'site_id' => $siteId
            ], 404);
        }

        return response()->json([
            'site_id' => $siteId,
            'sensors' => $sensorData,
            'last_updated' => $lastUpdated
        ]);
    }

    private function getSensorData($devIds, $sensors)
    {
        $data = [];

        $rawData = DB::table('tm_sensor_read')
            ->select('ds_id', 'dev_id', 'read_value', 'read_date')
            ->whereIn('ds_id', $sensors)
            ->whereIn('dev_id', $devIds)
            ->where('read_date', '<=', now()->setTimezone('Asia/Jakarta'))
            ->orderBy('read_date', 'DESC')
            ->get();

        foreach ($sensors as $sensor) {
            $sensorLimits = $this->getSensorThresholds($sensor);
            if (!$sensorLimits) continue;

            $sensorName = $this->getSensorName($sensor);
            $sensorData = $rawData->firstWhere('ds_id', $sensor);

            if (!$sensorData) continue;

            $readValue = $sensorData->read_value;
            $minValue = $sensorLimits->ds_min_norm_value;
            $maxValue = $sensorLimits->ds_max_norm_value;
            $minDangerAct = $sensorLimits->min_danger_action;
            $maxDangerAct = $sensorLimits->max_danger_action;

            if ($readValue >= $minValue && $readValue <= $maxValue) {
                $valueStatus = 'OK';
                $statusMessage = "$sensorName dalam kondisi normal";
                $actionMessage = '';
            } elseif ($readValue < $minValue) {
                $valueStatus = 'Danger';
                $statusMessage = "$sensorName di bawah batas normal";
                $actionMessage = $minDangerAct;
            } else {
                $valueStatus = 'Danger';
                $statusMessage = "$sensorName di atas batas normal";
                $actionMessage = $maxDangerAct;
            }

            $data[] = [
                'sensor' => $sensor,
                'sensor_name' => $sensorName,
                'read_value' => $readValue,
                'read_date' => $sensorData->read_date,
                'value_status' => $valueStatus,
                'status_message' => $statusMessage,
                'action_message' => $actionMessage
            ];
        }

        return $data;
    }

    private function getLastUpdatedDate($devIds)
    {
        $latestReadDate = DB::table('tm_sensor_read')
            ->whereIn('dev_id', $devIds)
            ->where('read_date', '<=', now()->setTimezone('Asia/Jakarta'))
            ->max('read_date');

        return $latestReadDate ? \Carbon\Carbon::parse($latestReadDate)->format('d-m-Y H:i') : null;
    }

    private function getSensorThresholds($ds_id)
    {
        return DB::table('td_device_sensors')
            ->where('ds_id', $ds_id)
            ->select('ds_min_norm_value', 'ds_max_norm_value', 'min_danger_action', 'max_danger_action')
            ->first();
    }

    public function getSensorName($ds_id)
    {
        return DB::table('td_device_sensors')
            ->where('ds_id', $ds_id)
            ->value('ds_name');
    }
}
