<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Realtime2Controller extends Controller
{
    public function getNitrogen()
    {
        $sensors = ['soil_nitro', 'soil_nitro1', 'soil_nitro2', 'soil_nitro3', 'soil_nitro5', 'soil_nitro6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value', DB::raw("
                CASE
                    WHEN read_value BETWEEN 20 AND 40 THEN 'OK'
                    WHEN read_value < 20 THEN 'Danger, Tambahkan pupuk'
                    WHEN read_value > 40 THEN 'Danger, Kurangi pemberian pupuk & lakukan irigasi'
                    ELSE 'Warning'
                END AS value_status
            "))
                ->where('ds_id', $sensor)
                ->orderBy('read_date', 'DESC')
                ->first();

            if ($sensorData) {
                $data[] = $sensorData;
            }
        }

        return response()->json($data);
    }

    public function getFosfor()
    {
        $sensors = ['soil_phos', 'soil_phos1', 'soil_phos2', 'soil_phos3', 'soil_phos5', 'soil_phos6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value', DB::raw("
                CASE
                    WHEN read_value BETWEEN 15 AND 30 THEN 'OK'
                    WHEN read_value < 14 THEN 'Danger, Tambahkan pupuk fosfor (SP-36)'
                    WHEN read_value > 31 THEN 'Danger, Kurangi pemberian pupuk fosfor dan tingkatkan irigasi'
                    ELSE 'Warning'
                END AS value_status
            "))
                ->where('ds_id', $sensor)
                ->orderBy('read_date', 'DESC')
                ->first();

            if ($sensorData) {
                $data[] = $sensorData;
            }
        }

        return response()->json($data);
    }

    public function getKalium()
    {
        $sensors = ['soil_pot', 'soil_pot1', 'soil_pot2', 'soil_pot3', 'soil_pot5', 'soil_pot6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value', DB::raw("
                    CASE
                        WHEN read_value BETWEEN 100 AND 200 THEN 'OK'
                        WHEN read_value < 99 THEN 'Danger, Tambahkan pupuk kalium (KCl)'
                        WHEN read_value > 201 THEN 'Danger, Kurangi pemberian pupuk kalium dan lakukan irigasi'
                        ELSE 'Warning'
                    END AS value_status
                "))
                ->where('ds_id', $sensor)
                ->orderBy('read_date', 'DESC')
                ->first();

            if ($sensorData) {
                $data[] = $sensorData;
            }
        }

        return response()->json($data);
    }

    public function getTDS()
    {
        $sensors = ['soil_tds', 'soil_tds1', 'soil_tds2', 'soil_tds3', 'soil_tds5', 'soil_tds6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value', DB::raw("
                CASE
                    WHEN read_value BETWEEN 500 AND 2000 THEN 'OK'
                    WHEN read_value < 499 THEN 'Danger, Tambahkan pupuk atau mineral'
                    WHEN read_value > 2001 THEN 'Danger, Lakukan irigasi mendalam untuk mengurangi konsentrasi zat terlarut'
                    ELSE 'Warning'
                END AS value_status
            "))
                ->where('ds_id', $sensor)
                ->orderBy('read_date', 'DESC')
                ->first();

            if ($sensorData) {
                $data[] = $sensorData;
            }
        }

        return response()->json($data);
    }


    public function getEC()
    {
        $sensors = ['soil_con', 'soil_con1', 'soil_con2', 'soil_con3', 'soil_con5', 'soil_con6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value', DB::raw("
                    CASE
                        WHEN read_value BETWEEN 1 AND 3 THEN 'OK'
                        WHEN read_value < 1 THEN 'Danger, Tingkatkan kadar zat terlarut'
                        WHEN read_value > 3 THEN 'Danger, Lakukan pengurangan zat terlarut'
                        ELSE 'Warning'
                    END AS value_status
                "))
                ->where('ds_id', $sensor)
                ->orderBy('read_date', 'DESC')
                ->first();

            if ($sensorData) {
                $data[] = $sensorData;
            }
        }

        return response()->json($data);
    }

    public function getSoilHum()
    {
        $sensors = ['soil_hum', 'soil_hum1', 'soil_hum2', 'soil_hum3', 'soil_hum5', 'soil_hum6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value', DB::raw("
                    CASE
                        WHEN read_value BETWEEN 30 AND 60 THEN 'OK'
                        WHEN read_value < 30 THEN 'Danger, Tingkatkan irigasi atau tambahkan bahan organik untuk mempertahankan kelembaban'
                        WHEN read_value > 60 THEN 'Danger, Perbaiki drainase untuk mencegah genangan air'
                        ELSE 'Warning'
                    END AS value_status
                "))
                ->where('ds_id', $sensor)
                ->orderBy('read_date', 'DESC')
                ->first();

            if ($sensorData) {
                $data[] = $sensorData;
            }
        }

        return response()->json($data);
    }

    public function getSoilPh()
    {
        $sensors = ['soil_ph', 'soil_ph1', 'soil_ph2', 'soil_ph3', 'soil_ph5', 'soil_ph6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value', DB::raw("
                    CASE
                        WHEN read_value BETWEEN 5.5 AND 7 THEN 'OK'
                        WHEN read_value < 4.5 THEN 'Danger, Tambahkan pupuk atau mineral'
                        WHEN read_value > 8 THEN 'Danger, Lakukan irigasi mendalam untuk mengurangi konsentrasi zat terlarut'
                        ELSE 'Warning'
                    END AS value_status
                "))
                ->where('ds_id', $sensor)
                ->orderBy('read_date', 'DESC')
                ->first();

            if ($sensorData) {
                $data[] = $sensorData;
            }
        }

        return response()->json($data);
    }

    public function getSoilTemp()
    {
        $sensors = ['soil_temp', 'soil_temp1', 'soil_temp2', 'soil_temp3', 'soil_temp5', 'soil_temp6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value', DB::raw("
                    CASE
                        WHEN read_value BETWEEN 20 AND 30 THEN 'OK'
                        WHEN read_value < 19 THEN 'Danger, Tingkatkan eksposur sinar matahari dan kurangi irigasi malam hari'
                        WHEN read_value > 31 THEN 'Danger, Gunakan mulsa dan lakukan irigasi lebih sering'
                        ELSE 'Warning'
                    END AS value_status
                "))
                ->where('ds_id', $sensor)
                ->orderBy('read_date', 'DESC')
                ->first();

            if ($sensorData) {
                $data[] = $sensorData;
            }
        }

        return response()->json($data);
    }

    public function index(Request $request, string $siteId)
    {

        if ($siteId === 'SITE001') {
            $nitrogenData = $this->getNitrogen();
            $fosforData = $this->getFosfor();
            $kaliumData = $this->getKalium();
            $tdsData = $this->getTDS();
            $ecData = $this->getEC();
            $soilhumData = $this->getSoilhum();
            $soilphData = $this->getSoilph();
            $soiltempData = $this->getSoiltemp();

            return response()->json([
                'site_id' => $siteId,
                'nitrogen' => $nitrogenData,
                'fosfor' => $fosforData,
                'kalium' => $kaliumData,
                'tds' => $tdsData,
                'ec' => $ecData,
                'soil_hum' => $soilhumData,
                'soil_ph' => $soilphData,
                'soil_temp' => $soiltempData
            ]);
        }

        if ($siteId === 'SITE002') {
            return response()->json([]);
        }

        return response()->json(['message' => 'Site tidak ditemukan'], 404);
    }
}
