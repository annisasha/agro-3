<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RealtimeController extends Controller
{
    public function getNitrogen()
    {
        $sensors = ['soil_nitro', 'soil_nitro1', 'soil_nitro2', 'soil_nitro3', 'soil_nitro5', 'soil_nitro6'];

        $data = [];

        foreach ($sensors as $sensor) {
            $sensorData = DB::table('tm_sensor_read')
                ->select('ds_id', 'read_value')
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
                ->select('ds_id', 'read_value')
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
                ->select('ds_id', 'read_value')
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
                ->select('ds_id', 'read_value')
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
                ->select('ds_id', 'read_value')
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
                ->select('ds_id', 'read_value')
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
                ->select('ds_id', 'read_value')
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
                ->select('ds_id', 'read_value')
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
