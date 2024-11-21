<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plant;
use Illuminate\Support\Facades\DB;

class Dashboard2Controller extends Controller
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
                'pt_id' => $plant->pt_id,
            ];
        });

        if ($plants->isEmpty()) {
            return response()->json(['message' => 'Tidak ada tanaman pada site ini'], 404);
        }

        $todos = [];
        foreach ($plants as $plant) {
            $plantTodos = DB::table('tr_plant_handling')
                ->where('pt_id', $plant['pt_id'])
                ->where('hand_day', '>=', $plant['age'])
                ->orderBy('hand_day')
                ->get()
                ->map(function ($todo) use ($plant) {
                    // Hitung tanggal kegiatan berdasarkan tanggal tanam
                    $plantDate = new \Carbon\Carbon($plant['pl_date_planting']);
                    $todoDate = $plantDate->addDays($todo->hand_day); // Menambahkan hand_day ke tanggal tanam

                    // Menghitung berapa hari lagi menuju kegiatan
                    $daysRemaining = now()->startOfDay()->diffInDays($todoDate->startOfDay());

                    return [
                        'hand_title' => $todo->hand_title,
                        'hand_desc' => $todo->hand_desc,
                        'hand_day' => $todo->hand_day,
                        'todo_date' => $todoDate->format('Y-m-d'), // Tanggal kegiatan
                        'days_remaining' => $daysRemaining, // Sisa hari menuju kegiatan
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
                'todos' => $todos
            ],
            200,
            [],
            JSON_PRETTY_PRINT
        );
    }


    // Fungsi untuk mengambil batas atas batas bawah dari sensor
    private function getSensorThresholds($ds_id)
    {
        return DB::table('td_device_sensor')
            ->where('ds_id', $ds_id)
            ->select('ds_min_value', 'ds_max_value', 'ds_min_val_warn', 'ds_max_val_warn')
            ->first();
    }


    // Fungsi untuk mengambil data suhu
    public function getTemperature($devIds)
    {
        // Mengambil batas-batas sensor untuk suhu
        $sensorLimits = $this->getSensorThresholds('temp');

        $minValue = $sensorLimits->ds_min_value;
        $maxValue = $sensorLimits->ds_max_value;
        $minDanger = $sensorLimits->ds_min_val_warn;
        $maxDanger = $sensorLimits->ds_max_val_warn;

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
            } elseif ($readValue < $minDanger) {
                $valueStatus = 'Danger';
                $statusMessage = 'Suhu di bawah batas normal';
                $actionMessage = 'Kurangi penyiraman malam hari untuk menghindari penurunan suhu';
            } elseif ($readValue > $maxDanger) {
                $valueStatus = 'Danger';
                $statusMessage = 'Suhu di atas batas normal';
                $actionMessage = 'Tambahkan irigasi untuk mengurangi efek panas';
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

        $minValue = $sensorLimits->ds_min_value;
        $maxValue = $sensorLimits->ds_max_value;
        $minDanger = $sensorLimits->ds_min_val_warn;
        $maxDanger = $sensorLimits->ds_max_val_warn;

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
            } elseif ($readValue < $minDanger) {
                $valueStatus = 'Danger';
                $statusMessage = 'Kelembapan di bawah batas normal';
                $actionMessage = 'Lakukan irigasi lebih sering';
            } elseif ($readValue > $maxDanger) {
                $valueStatus = 'Danger';
                $statusMessage = 'Kelembapan di atas batas normal';
                $actionMessage = 'Kurangi irigasi untuk mencegah kelembaban berlebihan';
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

        $minValue = $sensorLimits->ds_min_value;
        $maxValue = $sensorLimits->ds_max_value;
        $minDanger = $sensorLimits->ds_min_val_warn;
        $maxDanger = $sensorLimits->ds_max_val_warn;

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
            } elseif ($readValue < $minDanger) {
                $valueStatus = 'Danger';
                $statusMessage = 'Kecepatan angin terlalu rendah';
                $actionMessage = 'Waspadai potensi kerusakan tanaman';
            } elseif ($readValue > $maxDanger) {
                $valueStatus = 'Danger';
                $statusMessage = 'Kecepatan angin terlalu tinggi';
                $actionMessage = 'Gunakan penahan angin seperti pagar tanaman';
            } else {
                $valueStatus = 'Warning';
                $actionMessage = 'Kecepatan angin mendekati ambang batas, periksa kondisi lebih lanjut';
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

        $minValue = $sensorLimits->ds_min_value;
        $maxValue = $sensorLimits->ds_max_value;
        $minDanger = $sensorLimits->ds_min_val_warn;
        $maxDanger = $sensorLimits->ds_max_val_warn;

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
                $statusMessage = 'Kecerahan dalam kondisi normal';
            } elseif ($readValue < $minDanger) {
                $valueStatus = 'Danger';
                $actionMessage = 'Pastikan tanaman menerima pencahayaan yang cukup, atau gunakan lampu tambahan jika diperlukan';
            } elseif ($readValue > $maxDanger) {
                $valueStatus = 'Danger';
                $actionMessage = 'Gunakan jaring peneduh untuk melindungi tanaman dari sinar matahari langsung';
            } else {
                $valueStatus = 'Warning';
                $actionMessage = 'Kecerahan mendekati ambang batas, periksa kondisi lebih lanjut';
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

        $minValue = $sensorLimits->ds_min_value;
        $maxValue = $sensorLimits->ds_max_value;
        $minDanger = $sensorLimits->ds_min_val_warn;
        $maxDanger = $sensorLimits->ds_max_val_warn;

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
            } elseif ($readValue < $minDanger) {
                $valueStatus = 'Danger';
                $statusMessage = 'Curah hujan kurang';
                $actionMessage = 'Lakukan irigasi tambahan untuk memenuhi kebutuhan air tanaman';
            } elseif ($readValue > $maxDanger) {
                $valueStatus = 'Danger';
                $statusMessage = 'Curah hujan terlalu tinggi';
                $actionMessage = 'Perbaiki sistem drainase untuk menghindari genangan air di sawah';
            } else {
                $valueStatus = 'Warning';
                $actionMessage = 'Curah hujan mendekati ambang batas, periksa kondisi lebih lanjut';
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
