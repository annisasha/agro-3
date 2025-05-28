<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Plant;

class TanamanController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $plants = Plant::whereHas('device', function ($query) use ($user) {
            $query->where('user_id', $user->user_id);
        })->with('device.site')->get();

        return response()->json([
            'data' => $plants
        ]);
    }

    public function show(Request $request, $pl_id)
    {
        $user = $request->user();

        $plant = Plant::where('pl_id', $pl_id)
            ->whereHas('device', function ($query) use ($user) {
                $query->where('user_id', $user->user_id);
            })->first();

        if (!$plant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tanaman tidak ditemukan atau bukan milik Anda.',
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
        $user = $request->user();

        $plant = Plant::where('pl_id', $pl_id)
            ->whereHas('device', function ($query) use ($user) {
                $query->where('user_id', $user->user_id);
            })->first();

        if (!$plant) {
            return response()->json([
                'message' => 'Tanaman tidak ditemukan atau bukan milik Anda.'
            ], 404);
        }

        $validated = $request->validate([
            'dev_id' => 'nullable|string|max:32',
            'pt_id' => 'nullable|string|max:16',
            'pl_name' => 'nullable|string|max:128',
            'pl_desc' => 'nullable|string|max:128',
            'pl_date_planting' => 'nullable|date',
            'pl_area' => 'nullable|string', // ubah jadi string karena di DB varchar
            'pl_lat' => 'nullable|numeric',
            'pl_lon' => 'nullable|numeric',
        ]);

        Log::info('Validated update data:', $validated);

        $plant->update(array_merge($validated, ['pl_update' => now()]));
        $plant->refresh();

        return response()->json([
            'data' => $plant,
            'message' => 'Tanaman berhasil diperbarui.'
        ]);
    }


    public function destroy(Request $request, $pl_id)
    {
        $user = $request->user();

        $plant = Plant::where('pl_id', $pl_id)
            ->whereHas('device', function ($query) use ($user) {
                $query->where('user_id', $user->user_id);
            })->first();

        if (!$plant) {
            return response()->json([
                'message' => 'Tanaman tidak ditemukan atau bukan milik Anda.'
            ], 404);
        }

        $plant->delete();

        return response()->json([
            'message' => 'Tanaman berhasil dihapus.'
        ]);
    }
}
