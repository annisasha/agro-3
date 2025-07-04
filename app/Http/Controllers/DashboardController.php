<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        Log::info('Dashboard accessed by:', [
            'user_id' => $user?->user_id,
            'username' => $user?->user_name,
            'site_id' => $request->site_id,
        ]);

        if (!$request->site_id) {
            Log::warning('Dashboard request tanpa site_id oleh user: ' . ($user?->user_name ?? 'Guest'));
            return response()->json(['message' => 'Parameter site_id dibutuhkan'], 400);
        }

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $siteId = $request->input('site_id');
        if (empty($siteId)) {
            return response()->json(['message' => 'Pilih Site'], 400);
        }

        // ✅ DEBUG: Log informasi user & site
        Log::info('=== DEBUG: User Login Info ===', [
            'user_name' => $user->user_name,
            'user_id' => $user->id,
            'site_id' => $siteId
        ]);

        // ✅ DEBUG: Coba query langsung berdasarkan user_name
        $devQuery = DB::table('tm_device')
            ->where('site_id', $siteId)
            ->where('user_id', $user->user_id);

        Log::info('=== DEBUG: SQL Query Preview ===', [
            'sql' => $devQuery->toSql(),
            'bindings' => $devQuery->getBindings()
        ]);

        $devIds = $devQuery->pluck('dev_id');

        // ✅ DEBUG: Cek hasil query
        Log::info('=== DEBUG: Device IDs ===', $devIds->toArray());

        if ($devIds->isEmpty()) {
            return response()->json(['message' => 'Lahan ini tidak terdaftar sebagai milik anda'], 404);
        }

        // ... lanjutkan kode aslinya
        $temperatureData = $this->getTemperature($devIds);
        $humidityData = $this->getHumidity($devIds);
        $lastUpdated = $this->getLastUpdatedDate($devIds);

        $plants = Plant::whereIn('dev_id', $devIds)->get()->map(function ($plant) {
            $commodityVariety = $plant->getCommodityVariety();
            return [
                'pl_id' => $plant->pl_id,
                'pl_name' => $plant->pl_name,
                'pl_desc' => $plant->pl_desc,
                'pl_date_planting' => $plant->pl_date_planting,
                'age' => $plant->age(),
                'phase' => $plant->phase(),
                'timeto_harvest' => $plant->timetoHarvest(),
                'pt_id' => $plant->pt_id,
                'commodity' => $commodityVariety['commodity'],
                'variety' => $commodityVariety['variety']
            ];
        });

        if ($plants->isEmpty()) {
            return response()->json(['message' => 'Tidak ada tanaman pada lahan ini'], 404);
        }

        $todos = [];
        foreach ($plants as $plant) {
            $plantAge = $plant['age'];
            $plantDate = new \Carbon\Carbon($plant['pl_date_planting']);
            $plantTodos = DB::table('tr_plant_handling_copy')
                ->where('pt_id', $plant['pt_id'])
                ->orderBy('hand_day', 'ASC')
                ->get();

            $activeTodos = [];

            foreach ($plantTodos as $todo) {
                $todoStart = $todo->hand_day;
                $todoEnd = $todoStart + $todo->hand_day_toleran;

                if ($plantAge >= $todoStart && $plantAge <= $todoEnd) {
                    $todoDate = $plantDate->copy()->addDays($todo->hand_day);
                    $tolerantDate = $plantDate->copy()->addDays($todoEnd);

                    $activeTodos[] = [
                        'hand_title' => $todo->hand_title,
                        'hand_day' => $todo->hand_day,
                        'hand_day_toleran' => $todo->hand_day_toleran,
                        'fertilizer_type' => $todo->fertilizer_type ?? 'N/A',
                        'todo_date' => $todoDate->format('d-m-Y'),
                        'tolerant_date' => $tolerantDate->format('d-m-Y'),
                        'days_remaining' => $todoStart - $plantAge,
                        'days_tolerant_remaining' => $todoEnd - $plantAge,
                    ];
                }
            }

            $todos[] = [
                'plant_id' => $plant['pl_id'],
                'todos' => $activeTodos
            ];
        }

        return response()->json([
            'site_id' => $siteId,
            'temperature' => $temperatureData,
            'humidity' => $humidityData,
            'plants' => $plants,
            'todos' => $todos,
            'last_updated' => $lastUpdated
        ]);
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

    private function getSensorData($devIds, $sensors, $sensorType)
    {
        Log::info("🔍 Sensor IDs yang dicari: ", $sensors);
        Log::info("🔍 Device IDs yang dicari: ", $devIds->toArray());

        $start = microtime(true);

        // Ambil semua data sensor sekaligus
        $rawData = DB::table('tm_sensor_read')
            ->select('ds_id', 'dev_id', 'read_value', 'read_date')
            ->whereIn('ds_id', $sensors)
            ->whereIn('dev_id', $devIds)
            ->where('read_date', '<=', now()->setTimezone('Asia/Jakarta'))
            ->orderBy('read_date', 'DESC')
            ->get();

        Log::info("📦 Raw data sensor yang ditemukan:", $rawData->toArray());

        $results = [];

        foreach ($sensors as $sensor) {
            // Ambil data sensor pertama (terbaru) per sensor
            $sensorData = $rawData->firstWhere('ds_id', $sensor);

            $sensorLimits = $this->getSensorThresholds($sensor);
            if (!$sensorLimits) {
                Log::warning("⚠️ No thresholds found for sensor: $sensor");
                continue;
            }

            $sensorName = $this->getSensorName($sensor);

            if ($sensorData) {
                $readValue = $sensorData->read_value;
                $minValue = $sensorLimits->ds_min_norm_value;
                $maxValue = $sensorLimits->ds_max_norm_value;
                $minDangerAct = $sensorLimits->min_danger_action;
                $maxDangerAct = $sensorLimits->max_danger_action;

                // Evaluasi status
                if ($readValue >= $minValue && $readValue <= $maxValue) {
                    $valueStatus = 'OK';
                    $statusMessage = "$sensorType dalam kondisi normal";
                    $actionMessage = '';
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

                $results[] = [
                    'sensor' => $sensor,
                    'read_value' => $readValue,
                    'read_date' => $sensorData->read_date,
                    'value_status' => $valueStatus,
                    'status_message' => $statusMessage,
                    'action_message' => $actionMessage,
                    'sensor_name' => $sensorName
                ];
            }
        }

        $duration = round(microtime(true) - $start, 3);
        Log::info("✅ getSensorData() batch selesai dalam $duration detik");

        return $results;
    }


    public function getTemperature($devIds)
    {
        $sensors = ['env_temp'];
        return $this->getSensorData($devIds, $sensors, 'Suhu Lingkungan');
    }

    public function getHumidity($devIds)
    {
        $sensors = ['env_hum'];
        return $this->getSensorData($devIds, $sensors, 'Kelembapan Lingkungan');
    }

    public function getUserSites(Request $request)
    {
        $userId = $request->user()->id; // Mendapatkan ID pengguna yang sedang login

        $siteIds = DB::table('tm_device')
            ->where('user_id', $userId) // Asumsi ada kolom user_id di tm_device
            ->pluck('site_id')
            ->unique();

        if ($siteIds->isEmpty()) {
            return response()->json(['message' => 'Tidak ada site terkait untuk user ini'], 404);
        }

        return response()->json([
            'status' => 'success',
            'site_ids' => $siteIds
        ]);
    }
}
