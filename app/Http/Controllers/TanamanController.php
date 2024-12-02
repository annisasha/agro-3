<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Plant;

class TanamanController extends Controller
{
    public function index(Request $request)
    {
        $siteId = $request->input('site_id');

        $plants = Plant::whereHas('device', function ($query) use ($siteId) {
            $query->where('site_id', $siteId);
        })->get();

        return response()->json([
            'data' => $plants
        ]);
    }

    public function show($pl_id)
    {
        $plant = Plant::find($pl_id);

        if (!$plant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tanaman tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'data' => $plant
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pl_id' => 'required|string|max:32|unique:tm_plant',
            'dev_id' => 'nullable|string|max:32',
            'pt_id' => 'nullable|string|max:16',
            'pl_name' => 'nullable|string|max:128',
            'pl_desc' => 'nullable|string|max:128',
            'pl_date_planting' => 'nullable|date',
            'pl_area' => 'nullable|numeric',
            'pl_lat' => 'nullable|numeric',
            'pl_lon' => 'nullable|numeric'
        ]);

        $plant = Plant::create($validated);

        return response()->json([
            'data' => $plant,
            'message' => 'Tanaman berhasil ditambahkan.'
        ], 201);
    }

    public function update(Request $request, $pl_id)
    {
        $validated = $request->validate([
            'dev_id' => 'nullable|string|max:32',
            'pt_id' => 'nullable|string|max:16',
            'pl_name' => 'nullable|string|max:128',
            'pl_desc' => 'nullable|string|max:128',
            'pl_date_planting' => 'nullable|date',
            'pl_area' => 'nullable|numeric',
            'pl_lat' => 'nullable|numeric',
            'pl_lon' => 'nullable|numeric',
            'pl_update' => now()
        ]);

        $plant = Plant::find($pl_id);

        if (!$plant) {
            return response()->json([
                'message' => 'Tanaman tidak ditemukan.'
            ], 404);
        }

        $plant->update($validated);

        return response()->json([
            'data' => $plant,
            'message' => 'Tanaman berhasil diperbarui.'
        ]);
    }

    public function destroy($pl_id)
    {
        $plant = Plant::find($pl_id);

        if (!$plant) {
            return response()->json([
                'message' => 'Tanaman tidak ditemukan.'
            ], 404);
        }

        $plant->delete();

        return response()->json([
            'message' => 'Tanaman berhasil dihapus.'
        ], 200);
    }
}
