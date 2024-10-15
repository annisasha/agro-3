<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Area;
use App\Models\Site;

class AreaController extends Controller
{
    public function index()
    {
        $areas = Area::with('site')->get();
        return response()->json($areas);
    }

    public function show($id)
    {
        $area = Area::with('site')->find($id);
        if (!$area) {
            return response()->json(['message' => 'Area not found'], 404);
        }
        return response()->json($area);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'site_id' => 'required|exists:tm_site,site_id', 
            'type' => 'required|string|max:255',
        ]);

        $area = Area::create([
            'name' => $request->name,
            'site_id' => $request->site_id,
            'type' => $request->type,
        ]);

        return response()->json($area, 201);  
    }

      public function update(Request $request, $id)
    {
        $area = Area::find($id);
        if (!$area) {
            return response()->json(['message' => 'Area not found'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'site_id' => 'required|exists:tm_site,site_id',
            'type' => 'required|string|max:255',
        ]);

        $area->update([
            'name' => $request->name,
            'site_id' => $request->site_id,
            'type' => $request->type,
        ]);

        return response()->json($area);
    }

    public function destroy($id)
    {
        $area = Area::find($id);
        if (!$area) {
            return response()->json(['message' => 'Area not found'], 404);
        }

        $area->delete();
        return response()->json(['message' => 'Area deleted successfully']);
    }
}
