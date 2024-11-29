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
            return response()->json(['message' => 'Tidak ada tanaman pada site ini'], 404);
        }

        $todos = [];
        foreach ($plants as $plant) {
            $plantTodos = DB::table('tr_plant_handling_copy')
                ->where('pt_id', $plant['pt_id'])
                ->where('hand_day', '>=', $plant['age'])
                ->get()
                ->map(function ($todo) use ($plant) {
                    // Menghitung tanggal kegiatan berdasarkan tanggal tanam dan hand_day
                    $plantDate = new \Carbon\Carbon($plant['pl_date_planting']);

                    // Menghitung tanggal kegiatan sesuai hand_day
                    $todoDate = $plantDate->copy()->addDays($todo->hand_day);

                    // Menghitung tanggal kegiatan dengan toleransi (hand_day + hand_day_toleran)
                    $tolerantDate = $plantDate->copy()->addDays($todo->hand_day + $todo->hand_day_toleran);

                    // Menghitung sisa hari menuju kegiatan 
                    $daysRemaining = $todo->hand_day - $plant['age'];
                    $daysTolerantRemaining = ($todo->hand_day + $todo->hand_day_toleran) - $plant['age'];

                    return [
                        'hand_title' => $todo->hand_title,
                        'hand_day' => $todo->hand_day,
                        'hand_day_toleran' => $todo->hand_day_toleran,
                        'fertilizer_type' => $todo->fertilizer_type,
                        'todo_date' => $todoDate->format('d-m-Y'),
                        'tolerant_date' => $tolerantDate->format('d-m-Y'),
                        'days_remaining' => $daysRemaining,
                        'days_tolerant_remaining' => $daysTolerantRemaining
                    ];
                });

            $todos[] = [
                'plant_id' => $plant['pl_id'],
                'todos' => $plantTodos
            ];
        }

        return response()->json(
            [
                'site_id' => $siteId,
                'temperature' => $temperatureData,
                'humidity' => $humidityData,
                'wind' => $windData,
                'lux' => $luxData,
                'rain' => $rainData,
                'plants' => $plants,
                'todos' => $todos,
                'last_updated' => $lastUpdated
            ],
            200,
            [],
            JSON_PRETTY_PRINT
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
        return DB::table('td_device_sensors')
            ->where('ds_id', $ds_id)
            ->select('ds_min_norm_value', 'ds_max_norm_value')
            ->first();
    }


    // Fungsi untuk mengambil data suhu
    public function getTemperature($devIds)
    {
        $sensorLimits = $this->getSensorThresholds('temp');

        $minValue = $sensorLimits->ds_min_norm_value;
        $maxValue = $sensorLimits->ds_max_norm_value;

        $data = DB::table('tm_sensor_read')
            ->select('read_value')
            ->where('ds_id', 'temp')
            ->whereIn('dev_id', $devIds)
            ->orderBy('read_date', 'DESC')
            ->first();

        $valueStatus = '';
        $actionMessage = '';
        $statusMessage = '';

        if ($data) {
            $readValue = $data->read_value;
            if ($readValue >= $minValue && $readValue <= $maxValue) {
                $valueStatus = 'OK';
                $statusMessage = 'Suhu dalam kondisi normal';
            } elseif ($readValue < $minValue) {
                $valueStatus = 'Danger';
                $statusMessage = 'Suhu terlalu rendah';
                $actionMessage = 'Kurangi penyiraman malam hari untuk menghindari penurunan suhu';
            } elseif ($readValue > $maxValue) {
                $valueStatus = 'Danger';
                $statusMessage = 'Suhu terlalu tinggi';
                $actionMessage = 'Atur pengairan untuk mengurangi efek panas';
            } else {
                $valueStatus = 'Warning';
                $statusMessage = 'Suhu mendekati ambang batas';
                $actionMessage = 'Periksa kondisi lebih lanjut';
            }
        }

        return [
            'data' => $data,
            'value_status' => $valueStatus,
            'status_message' => $statusMessage,
            'action_message' => $actionMessage
        ];
    }


    // Fungsi untuk mengambil data kelembapan
    public function getHumidity($devIds)
    {
        $sensorLimits = $this->getSensorThresholds('hum');

        $minValue = $sensorLimits->ds_min_norm_value;
        $maxValue = $sensorLimits->ds_max_norm_value;

        $data = DB::table('tm_sensor_read')
            ->select('read_value')
            ->where('ds_id', 'hum')
            ->whereIn('dev_id', $devIds)
            ->orderBy('read_date', 'DESC')
            ->first();

        $valueStatus = '';
        $actionMessage = '';
        $statusMessage = '';

        if ($data) {
            $readValue = $data->read_value;
            if ($readValue >= $minValue && $readValue <= $maxValue) {
                $valueStatus = 'OK';
                $statusMessage = 'Kelembapan dalam kondisi normal';
            } elseif ($readValue < $minValue) {
                $valueStatus = 'Danger';
                $statusMessage = 'Kelembapan terlalu rendah';
                $actionMessage = 'Tambahkan pengairan lebih intensif';
            } elseif ($readValue > $maxValue) {
                $valueStatus = 'Danger';
                $statusMessage = 'Kelembapan terlalu tinggi';
                $actionMessage = 'Kurangi pengairan, untuk mencegah kelembapan berlebihan';
            } else {
                $valueStatus = 'Warning';
                $statusMessage = 'Kelembapan mendekati ambang batas';
                $actionMessage = 'Periksa kondisi lebih lanjut';
            }
        }

        return [
            'data' => $data,
            'value_status' => $valueStatus,
            'status_message' => $statusMessage,
            'action_message' => $actionMessage
        ];
    }



    // Fungsi untuk mengambil data kecepatan angin
    public function getWind($devIds)
    {
        $sensorLimits = $this->getSensorThresholds('wind');

        $minValue = $sensorLimits->ds_min_norm_value;
        $maxValue = $sensorLimits->ds_max_norm_value;


        $data = DB::table('tm_sensor_read')
            ->select('read_value')
            ->where('ds_id', 'wind')
            ->whereIn('dev_id', $devIds)
            ->orderBy('read_date', 'DESC')
            ->first();

        $valueStatus = '';
        $actionMessage = '';
        $statusMessage = '';

        if ($data) {
            $readValue = $data->read_value;
            if ($readValue >= $minValue && $readValue <= $maxValue) {
                $valueStatus = 'OK';
                $statusMessage = 'Kecepatan angin dalam kondisi normal';
            } elseif ($readValue > $minValue) {
                $valueStatus = 'Danger';
                $statusMessage = 'Angin terlalu kencang';
                $actionMessage = 'Gunakan penahan angin seperti pagar tanaman';
            }
        }

        return [
            'data' => $data,
            'value_status' => $valueStatus,
            'status_message' => $statusMessage,
            'action_message' => $actionMessage
        ];
    }



    // Fungsi untuk mengambil data kecerahan
    public function getLux($devIds)
    {
        $sensorLimits = $this->getSensorThresholds('ilum');

        $minValue = $sensorLimits->ds_min_norm_value;
        $maxValue = $sensorLimits->ds_max_norm_value;

        $data = DB::table('tm_sensor_read')
            ->select('read_value')
            ->where('ds_id', 'ilum')
            ->whereIn('dev_id', $devIds)
            ->orderBy('read_date', 'DESC')
            ->first();

        $valueStatus = '';
        $actionMessage = '';
        $statusMessage = '';

        if ($data) {
            $readValue = $data->read_value;
            if ($readValue >= $minValue && $readValue <= $maxValue) {
                $valueStatus = 'OK';
                $statusMessage = 'Intensitas cahaya dalam kondisi normal';
            } elseif ($readValue < $minValue) {
                $valueStatus = 'Danger';
                $statusMessage = 'Intensitas cahaya terlalu rendah';
                $actionMessage = 'Pastikan tanaman menerima pencahayaan yang cukup, atau gunakan lampu tambahan jika diperlukan';
            } elseif ($readValue > $maxValue) {
                $valueStatus = 'Danger';
                $statusMessage = 'Intensitas cahaya terlalu tinggi';
                $actionMessage = 'Gunakan jaring peneduh untuk melindungi tanaman dari sinar matahari langsung';
            } else {
                $valueStatus = 'Warning';
                $statusMessage = 'Intensitas cahaya mendekati ambang batas';
                $actionMessage = 'Periksa kondisi lebih lanjut';
            }
        }

        return [
            'data' => $data,
            'value_status' => $valueStatus,
            'status_message' => $statusMessage,
            'action_message' => $actionMessage
        ];
    }



    // Fungsi untuk mengambil data curah hujan
    public function getRain($devIds)
    {
        $sensorLimits = $this->getSensorThresholds('rain');

        $minValue = $sensorLimits->ds_min_norm_value;
        $maxValue = $sensorLimits->ds_max_norm_value;

        $data = DB::table('tm_sensor_read')
            ->select('read_value')
            ->where('ds_id', 'rain')
            ->whereIn('dev_id', $devIds)
            ->orderBy('read_date', 'DESC')
            ->first();

        $valueStatus = '';
        $actionMessage = '';
        $statusMessage = '';

        if ($data) {
            $readValue = $data->read_value;
            if ($readValue >= $minValue && $readValue <= $maxValue) {
                $valueStatus = 'OK';
                $statusMessage = 'Curah hujan dalam kondisi normal';
            } elseif ($readValue < $minValue) {
                $valueStatus = 'Danger';
                $statusMessage = 'Curah hujan terlalu rendah';
                $actionMessage = 'Lakukan irigasi tambahan untuk memenuhi kebutuhan air tanaman';
            } elseif ($readValue > $maxValue) {
                $valueStatus = 'Danger';
                $statusMessage = 'Curah hujan terlalu tinggi';
                $actionMessage = 'Gunakan jaring peneduh untuk melindungi tanaman dari sinar matahari langsung';
            } else {
                $valueStatus = 'Warning';
                $statusMessage = 'Perbaiki sistem drainase untuk menghindari genangan air di sawah';
                $actionMessage = 'Periksa kondisi lebih lanjut';
            }
        }

        return [
            'data' => $data,
            'value_status' => $valueStatus,
            'status_message' => $statusMessage,
            'action_message' => $actionMessage
        ];
    }
}
