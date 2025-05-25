<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Riwayat2Controller extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        Log::info('Dashboard accessed by:', [
            'user_id' => $user?->user_id,
            'username' => $user?->user_name,
            'site_id' => $request->site_id,
        ]);

        $siteId = $request->input('site_id');
        $selectedSensors = $request->input('sensors', []);
        $selectedAreas = $request->input('areas', ['all']);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        Log::info('📥 Input Request:', [
            'site_id' => $siteId,
            'selected_sensors_raw' => $selectedSensors,
            'selected_areas_raw' => $selectedAreas,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        if (empty($startDate) || empty($endDate)) {
            return response()->json(['message' => 'Tanggal mulai dan akhir harus diisi.'], 400);
        }

        // Ambil semua sensor yang ada di site
        $siteSensors = DB::table('td_device_sensors')
            ->join('tm_device', 'tm_device.dev_id', '=', 'td_device_sensors.dev_id')
            ->where('tm_device.site_id', $siteId)
            ->pluck('td_device_sensors.ds_id');

        Log::info('🔌 Sensor IDs dari site:', $siteSensors->toArray());

        if ($siteSensors->isEmpty()) {
            return response()->json(['message' => 'Tidak ada sensor yang terhubung dengan site ini.'], 404);
        }

        if (in_array('all', $selectedSensors)) {
            if (in_array('all', $selectedAreas)) {
                $selectedSensors = $siteSensors->toArray();
            } else {
                // Ambil ds_id dari tabel td_device_sensors yang ds_name mengandung "area X"
                $filteredDsIds = DB::table('td_device_sensors')
                    ->where(function ($query) use ($selectedAreas) {
                        foreach ($selectedAreas as $area) {
                            $query->orWhere('ds_name', 'like', '%area ' . $area . '%');
                        }
                    })
                    ->pluck('ds_id')
                    ->toArray();

                // Intersect dengan sensor yang memang terdaftar di site
                $selectedSensors = array_values(array_intersect($siteSensors->toArray(), $filteredDsIds));
            }
        }


        Log::info('✅ Final sensor list setelah filter area:', $selectedSensors);

        if (empty($selectedSensors)) {
            return response()->json(['message' => 'Tidak ada sensor yang sesuai dengan filter area.'], 404);
        }

        $start = Carbon::parse($startDate)->startOfDay()->toDateTimeString();
        $end = Carbon::parse($endDate)->endOfDay()->toDateTimeString();

        Log::info('📅 Final filter date range:', [$start, $end]);

        $data = DB::table('tm_sensor_read')
            ->select(
                'ds_id',
                DB::raw('MIN(read_date) as read_date'),
                DB::raw('MAX(read_value) as read_value')
            )
            ->whereIn('ds_id', $selectedSensors)
            ->whereBetween('read_date', [$start, $end])
            ->groupBy(DB::raw('DATE(read_date), ds_id'))
            ->orderBy('read_date', 'ASC')
            ->get();

        Log::info('📦 Jumlah data ditemukan:', ['count' => $data->count()]);
        Log::info('📦 Data preview:', $data->take(3)->toArray());

        if ($data->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data untuk rentang waktu dan area yang dipilih.'], 404);
        }

        $result = $data->map(function ($item) {
            $sensorName = DB::table('td_device_sensors')
                ->where('ds_id', $item->ds_id)
                ->value('ds_name');

            return [
                'ds_id' => $item->ds_id,
                'sensor_name' => $sensorName ?? $item->ds_id,
                'read_date' => $item->read_date,
                'read_value' => $item->read_value,
            ];
        });

        return response()->json($result);
    }
}
