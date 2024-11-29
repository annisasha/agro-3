<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TanamanController extends Controller
{

    # Untuk edit informasi tanaman
    public function update(Request $request, $pl_id)
    {
        $validated = $request->validate([
            'pl_name' => 'required|string|max:255',
            'pl_desc' => 'nullable|string',
            'pl_date_planting' => 'required|date',
            'pl_area' => 'required|numeric',
            'pl_lat' => 'required|numeric',
            'pl_lon' => 'required|numeric',
        ]);

        $plant = DB::table('tm_plant')->where('pl_id', $pl_id)->first();

        if (!$plant) {
            return response()->json(['message' => 'Tanaman tidak ditemukan.'], 404);
        }

        DB::table('tm_plant')
            ->where('pl_id', $pl_id)
            ->update([
                'pl_name' => $validated['pl_name'],
                'pl_desc' => $validated['pl_desc'],
                'pl_date_planting' => $validated['pl_date_planting'],
                'pl_area' => $validated['pl_area'],
                'pl_lat' => $validated['pl_lat'],
                'pl_lon' => $validated
            ]);

        return response()->json(['message' => 'Informasi tanaman berhasil diperbarui.']);
    }
}
