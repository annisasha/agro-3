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
                'soil_temp' => $soiltempData
            ],
            200,
            [],
            JSON_PRETTY_PRINT
        );
    }

    // Fungsi untuk mengambil batas atas batas bawah dari sensor
    private function getSensorThresholds($ds_id)
    {
        $sensorThresholds = DB::table('td_device_sensor')
            ->where('ds_id', $ds_id)
            ->select('ds_min_norm_value', 'ds_max_norm_value', 'ds_min_val_warn', 'ds_max_val_warn')
            ->first();

        if (!$sensorThresholds) {
            Log::error("No thresholds found for sensor ID: $ds_id");
            return null;
        }

        return $sensorThresholds;
    }


    // Fungsi untuk mengambil data nitrogen
    public function getNitrogen($devIds)
    {
        $sensors = ['soil_nitro1', 'soil_nitro2', 'soil_nitro3', 'soil_nitro5', 'soil_nitro6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value')
                ->where('ds_id', $sensor)
                ->whereIn('dev_id', $devIds)
                ->orderBy('read_date', 'DESC')
                ->first();

            $valueStatus = '';
            $actionMessage = '';
            $statusMessage = '';
            $sensorName = ucwords(str_replace('_', ' ', $sensor));

            if ($sensorData) {
                $sensorLimits = $this->getSensorThresholds($sensor);

                if ($sensorLimits) {
                    $minValue = $sensorLimits->ds_min_norm_value;
                    $maxValue = $sensorLimits->ds_max_norm_value;
                    $minDanger = $sensorLimits->ds_min_val_warn;
                    $maxDanger = $sensorLimits->ds_max_val_warn;

                    $readValue = $sensorData->read_value;

                    if ($readValue >= $minValue && $readValue <= $maxValue) {
                        $valueStatus = 'OK';
                        $statusMessage = 'Nitrogen dalam kondisi normal';
                        $actionMessage = '';
                    } elseif ($readValue < $minDanger) {
                        $valueStatus = 'Danger';
                        $statusMessage = 'Tingkat Nitrogen terlalu rendah';
                        $actionMessage = 'Tambahkan pupuk nitrogen';
                    } elseif ($readValue > $maxDanger) {
                        $valueStatus = 'Danger';
                        $statusMessage = 'Tingkat Nitrogen terlalu tinggi';
                        $actionMessage = 'Kurangi pemberian pupuk nitrogen dan lakukan irigasi';
                    } else {
                        $valueStatus = 'Warning';
                        $statusMessage = 'Nitrogen mendekati ambang batas';
                        $actionMessage = 'Periksa kondisi lebih lanjut';
                    }
                }
            }

            $data[] = [
                'sensor' => $sensor,
                'data' => $sensorData,
                'value_status' => $valueStatus,
                'status_message' => $statusMessage,
                'action_message' => $actionMessage,
                'sensor_name' => $sensorName
            ];
        }

        return $data;
    }


    // Fungsi untuk mengambil data fosfor
    public function getFosfor($devIds)
    {
        $sensors = ['soil_phos1', 'soil_phos2', 'soil_phos3', 'soil_phos5', 'soil_phos6'];
        $sensors = ['soil_phos1', 'soil_phos2', 'soil_phos3', 'soil_phos5', 'soil_phos6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value')
                ->where('ds_id', $sensor)
                ->whereIn('dev_id', $devIds)
                ->orderBy('read_date', 'DESC')
                ->first();

            $valueStatus = '';
            $actionMessage = '';
            $statusMessage = '';
            $sensorName = ucwords(str_replace('_', ' ', $sensor));

            if ($sensorData) {
                $sensorLimits = $this->getSensorThresholds($sensor);

                if ($sensorLimits) {
                    $minValue = $sensorLimits->ds_min_norm_value;
                    $maxValue = $sensorLimits->ds_max_norm_value;
                    $minDanger = $sensorLimits->ds_min_val_warn;
                    $maxDanger = $sensorLimits->ds_max_val_warn;

                    $readValue = $sensorData->read_value;

                    if ($readValue >= $minValue && $readValue <= $maxValue) {
                        $valueStatus = 'OK';
                        $statusMessage = 'Fosfor dalam kondisi normal';
                        $actionMessage = '';
                    } elseif ($readValue < $minDanger) {
                        $valueStatus = 'Danger';
                        $statusMessage = 'Tingkat Fosfor kurang';
                        $actionMessage = 'Tambahkan pupuk fosfor (SP-36)';
                    } elseif ($readValue > $maxDanger) {
                        $valueStatus = 'Danger';
                        $statusMessage = 'Fosfor di atas batas';
                        $actionMessage = 'Kurangi pemberian pupuk fosfor dan tingkatkan irigasi';
                    } else {
                        $valueStatus = 'Warning';
                        $statusMessage = 'Fosfor mendekati ambang batas';
                        $actionMessage = 'Periksa kondisi lebih lanjut';
                    }
                }
            }

            $data[] = [
                'sensor' => $sensor,
                'data' => $sensorData,
                'value_status' => $valueStatus,
                'status_message' => $statusMessage,
                'action_message' => $actionMessage,
                'sensor_name' => $sensorName
            ];
        }

        return $data;
    }


    // Fungsi untuk mengambil data kalium
    public function getKalium($devIds)
    {
        $sensors = ['soil_pot1', 'soil_pot2', 'soil_pot3', 'soil_pot5', 'soil_pot6'];
        $sensors = ['soil_pot1', 'soil_pot2', 'soil_pot3', 'soil_pot5', 'soil_pot6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value')
                ->where('ds_id', $sensor)
                ->whereIn('dev_id', $devIds)
                ->orderBy('read_date', 'DESC')
                ->first();

            $valueStatus = '';
            $actionMessage = '';
            $statusMessage = '';
            $sensorName = ucwords(str_replace('_', ' ', $sensor));

            if ($sensorData) {
                $sensorLimits = $this->getSensorThresholds('kalium');

                if ($sensorLimits) {
                    $minValue = $sensorLimits->ds_min_norm_value;
                    $maxValue = $sensorLimits->ds_max_norm_value;
                    $minDanger = $sensorLimits->ds_min_val_warn;
                    $maxDanger = $sensorLimits->ds_max_val_warn;

                    $readValue = $sensorData->read_value;

                    if ($readValue >= $minValue && $readValue <= $maxValue) {
                        $valueStatus = 'OK';
                        $statusMessage = 'Kalium dalam kondisi normal';
                        $actionMessage = '';
                    } elseif ($readValue < $minDanger) {
                        $valueStatus = 'Danger';
                        $statusMessage = 'Tingkat Kalium terlalu rendah';
                        $actionMessage = 'Tambahkan pupuk kalium (KCl)';
                    } elseif ($readValue > $maxDanger) {
                        $valueStatus = 'Danger';
                        $statusMessage = 'Kalium terlalu tinggi';
                        $actionMessage = 'Kurangi pemberian pupuk kalium dan lakukan irigasi';
                    } else {
                        $valueStatus = 'Warning';
                        $statusMessage = 'Kalium mendekati ambang batas';
                        $actionMessage = 'Periksa kondisi lebih lanjut';
                    }
                }
            }

            $data[] = [
                'sensor' => $sensor,
                'data' => $sensorData,
                'value_status' => $valueStatus,
                'status_message' => $statusMessage,
                'action_message' => $actionMessage,
                'sensor_name' => $sensorName
            ];
        }

        return $data;
    }


    // Fungsi untuk mengambil data TDS
    public function getTDS($devIds)
    {
        $sensors = ['soil_tds1', 'soil_tds2', 'soil_tds3', 'soil_tds5', 'soil_tds6'];
        $sensors = ['soil_tds1', 'soil_tds2', 'soil_tds3', 'soil_tds5', 'soil_tds6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value')
                ->where('ds_id', $sensor)
                ->whereIn('dev_id', $devIds)
                ->orderBy('read_date', 'DESC')
                ->first();

            if ($sensorData) {
                $data[] = $sensorData;
            }
        }

        return [
            'data' => $data
        ];
    }


    // Fungsi untuk mengambil data EC
    public function getEC($devIds)
    {
        $sensors = ['soil_con1', 'soil_con2', 'soil_con3', 'soil_con5', 'soil_con6'];
        $sensors = ['soil_con1', 'soil_con2', 'soil_con3', 'soil_con5', 'soil_con6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value')
                ->where('ds_id', $sensor)
                ->whereIn('dev_id', $devIds)
                ->orderBy('read_date', 'DESC')
                ->first();

            if ($sensorData) {
                $data[] = $sensorData;
            }
        }

        return [
            'data' => $data
        ];
    }

    // Fungsi untuk mengambil data kelembapan tanah
    public function getSoilHum($devIds)
    {
        $sensors = ['soil_hum1', 'soil_hum2', 'soil_hum3', 'soil_hum5', 'soil_hum6'];
        $sensors = ['soil_hum1', 'soil_hum2', 'soil_hum3', 'soil_hum5', 'soil_hum6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value')
                ->where('ds_id', $sensor)
                ->whereIn('dev_id', $devIds)
                ->orderBy('read_date', 'DESC')
                ->first();

            if ($sensorData) {
                $data[] = $sensorData;
            }
        }

        return [
            'data' => $data
        ];
    }

    // Fungsi untuk mengambil data ph tanah
    public function getSoilPh($devIds)
    {
        $sensors = ['soil_ph1', 'soil_ph2', 'soil_ph3', 'soil_ph5', 'soil_ph6'];
        $sensors = ['soil_ph1', 'soil_ph2', 'soil_ph3', 'soil_ph5', 'soil_ph6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value')
                ->where('ds_id', $sensor)
                ->whereIn('dev_id', $devIds)
                ->orderBy('read_date', 'DESC')
                ->first();

            $sensorLimits = $this->getSensorThresholds($sensor);

            $minValue = $sensorLimits->ds_min_norm_value / 10;
            $maxValue = $sensorLimits->ds_max_norm_value / 10;
            $minDanger = $sensorLimits->ds_min_val_warn / 10;
            $maxDanger = $sensorLimits->ds_max_val_warn / 10;

            $valueStatus = '';
            $actionMessage = '';
            $statusMessage = '';
            $sensorName = ucwords(str_replace('_', ' ', $sensor));

            if ($sensorData) {
                $sensorData->read_value = $sensorData->read_value / 10;

                $readValue = $sensorData->read_value;

                if ($readValue >= $minValue && $readValue <= $maxValue) {
                    $valueStatus = 'OK';
                    $statusMessage = 'pH tanah dalam kondisi normal';
                } elseif ($readValue < $minDanger) {
                    $valueStatus = 'Danger';
                    $statusMessage = 'Tingkat pH tanah di bawah batas';
                    $actionMessage = 'Tambahkan kapur dolomit ke tanah untuk menaikkan pH dan memperbaiki keasaman tanah';
                } elseif ($readValue > $maxDanger) {
                    $valueStatus = 'Danger';
                    $statusMessage = 'Tingkat pH tanah di atas batas';
                    $actionMessage = 'Tambahkan belerang (sulfur) untuk menurunkan pH tanah secara bertahap';
                } else {
                    $valueStatus = 'Warning';
                    $statusMessage = 'Tingkat pH tanah mendekati ambang batas';
                    $actionMessage = 'Periksa kondisi lebih lanjut';
                }

                $data[] = [
                    'sensor' => $sensor,
                    'data' => $sensorData,
                    'value_status' => $valueStatus,
                    'status_message' => $statusMessage,
                    'action_message' => $actionMessage,
                    'sensor_name' => $sensorName
                ];
            }
        }

        return $data;
    }


    // Fungsi untuk mengambil data temperature tanah
    public function getSoilTemp($devIds)
    {
        $sensors = ['soil_temp1', 'soil_temp2', 'soil_temp3', 'soil_temp5', 'soil_temp6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value')
                ->where('ds_id', $sensor)
                ->whereIn('dev_id', $devIds)
                ->orderBy('read_date', 'DESC')
                ->first();

            $sensorLimits = $this->getSensorThresholds($sensor);

            $minValue = $sensorLimits->ds_min_norm_value;
            $maxValue = $sensorLimits->ds_max_norm_value;
            $minDanger = $sensorLimits->ds_min_val_warn;
            $maxDanger = $sensorLimits->ds_max_val_warn;

            $valueStatus = '';
            $actionMessage = '';
            $statusMessage = '';
            $sensorName = ucwords(str_replace('_', ' ', $sensor));

            if ($sensorData) {
                $readValue = $sensorData->read_value;

                if ($readValue >= $minValue && $readValue <= $maxValue) {
                    $valueStatus = 'OK';
                    $statusMessage = 'Suhu tanah dalam kondisi normal';
                } elseif ($readValue < $minDanger) {
                    $valueStatus = 'Danger';
                    $statusMessage = 'Suhu tanah di bawah batas';
                    $actionMessage = 'Tingkatkan eksposur sinar matahari dan kurangi irigasi malam hari';
                } elseif ($readValue > $maxDanger) {
                    $valueStatus = 'Danger';
                    $statusMessage = 'Suhu tanah di atas batas';
                    $actionMessage = 'Gunakan mulsa dan lakukan irigasi lebih sering';
                } else {
                    $valueStatus = 'Warning';
                    $statusMessage = 'Tingkat suhu tanah mendekati ambang batas';
                    $actionMessage = 'Periksa kondisi lebih lanjut';
                }

                $data[] = [
                    'sensor' => $sensor,
                    'data' => $sensorData,
                    'value_status' => $valueStatus,
                    'status_message' => $statusMessage,
                    'action_message' => $actionMessage,
                    'sensor_name' => $sensorName
                ];
            }
        }

        return $data;
    }
}
