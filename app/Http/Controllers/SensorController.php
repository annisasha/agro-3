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
    $sensor = SensorDevice::findOrFail($id);
    return response()->json($sensor);
}

public function update(Request $request, $id)
{
    $request->validate([
        'ds_id' => 'required|string|max:32',
        'dev_id' => 'required|string|max:32',
        'unit_id' => 'nullable|string|max:32',
        'dc_normal_value' => 'nullable|numeric',
        'ds_min_norm_value' => 'nullable|numeric',
        'ds_max_norm_value' => 'nullable|numeric',
        'ds_min_value' => 'nullable|numeric',
        'ds_max_value' => 'nullable|numeric',
        'ds_min_val_warn' => 'nullable|numeric',
        'ds_max_val_warn' => 'nullable|numeric',
        'ds_name' => 'nullable|string|max:128',
        'ds_address' => 'nullable|string|max:32',
        'ds_seq' => 'nullable|integer',
        'ds_sts' => 'nullable|integer',
        'ds_update' => 'nullable|date',
    ]);

    $sensor = SensorDevice::findOrFail($id);
    $sensor->update($request->all());

    return response()->json($sensor);
}

public function destroy($id)
{
    $sensor = SensorDevice::findOrFail($id);
    $sensor->delete();

    return response()->json(['message' => 'Sensor device berhasil dihapus'], 200);
}
}