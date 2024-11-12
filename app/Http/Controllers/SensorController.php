<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SensorDevice;

class SensorController extends Controller
{
    public function index()
    {
        $sensors = SensorDevice::all();
        return response()->json($sensors);
    }

    public function show($id)
    {
        $sensor = SensorDevice::find($id);
        if (!$sensor) {
            return response()->json(['message' => 'Sensor device tidak ditemukan'], 404);
        }
        return response()->json($sensor);
    }

    public function store(Request $request)
    {
        $request->validate([
            'unit_id' => 'required|string|max:32',
            'unit_name' => 'required|string|max:64',
            'unit_name_idn' => 'nullable|string|max:32',
            'unit_symbol' => 'nullable|string|max:4',
            'unit_sts' => 'nullable|integer|digits_between:0,1',
            'unit_update' => 'nullable|date',
            'area' => 'nullable|string|max:32',
            'active' => 'nullable|string|max:8',
            'min_norm_value' => 'nullable|string|max:64',
            'max_norm_value' => 'nullable|string|max:64',
        ]);

        $sensor = SensorDevice::create($request->all());

        return response()->json($sensor, 201);
    }

    public function update(Request $request, $id)
    {
        $sensor = SensorDevice::find($id);
        if (!$sensor) {
            return response()->json(['message' => 'Sensor device tidak ditemukan'], 404);
        }

        $request->validate([
            'unit_id' => 'required|string|max:32',
            'unit_name' => 'required|string|max:64',
            'unit_name_idn' => 'nullable|string|max:32',
            'unit_symbol' => 'nullable|string|max:4',
            'unit_sts' => 'nullable|integer|digits_between:0,1',
            'unit_update' => 'nullable|date',
            'area' => 'nullable|string|max:32',
            'active' => 'nullable|string|max:8',
            'min_norm_value' => 'nullable|string|max:64',
            'max_norm_value' => 'nullable|string|max:64',
        ]);

        $sensor->update($request->all());

        return response()->json($sensor);
    }

    public function destroy($id)
    {
        $sensor = SensorDevice::find($id);
        if (!$sensor) {
            return response()->json(['message' => 'Sensor device tidak ditemukan'], 404);
        }

        $sensor->delete();
        return response()->json(['message' => 'Sensor device berhasil dihapus']);
    }
}
