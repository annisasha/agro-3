<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache; // Tambahkan ini

class RingkasanDataController extends Controller
{
    /**
     * Mengambil ringkasan data sensor (rata-rata, min, max) berdasarkan interval waktu.
     * Interval bisa 'day', 'week', 'month', 'hour'.
     */
    public function getSummary(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'site_id' => 'required|string',
            'areas' => 'required|array',
            'sensors' => 'nullable|array',
            'start_date' => 'required|date_format:Y-m-d H:i:s', // Menerima hingga detik
            'end_date' => 'required|date_format:Y-m-d H:i:s|after_or_equal:start_date',
            'interval' => 'required|string|in:day,week,month,hour', // Tipe interval
        ]);

        $siteId = $request->input('site_id');
        $areas = $request->input('areas');
        $selectedSensors = $request->input('sensors', []);
        $interval = $request->input('interval');

        // Pastikan tanggal diparsing dengan benar
        $carbonStartDate = Carbon::parse($request->input('start_date'));
        $carbonEndDate = Carbon::parse($request->input('end_date'));

        // Buat kunci cache unik berdasarkan semua parameter input
        $cacheKey = 'summary_data_' . md5(json_encode([
            $siteId,
            $areas,
            $selectedSensors,
            $carbonStartDate->toDateTimeString(),
            $carbonEndDate->toDateTimeString(),
            $interval
        ]));

        // Coba ambil data dari cache terlebih dahulu
        if (Cache::has($cacheKey)) {
            Log::info('✅ Data ringkasan diambil dari cache.');
            return response()->json(Cache::get($cacheKey), 200, [], JSON_PRETTY_PRINT);
        }

        // 2. Tentukan Mapping Sensor Berdasarkan Area (Sama seperti RiwayatController)
        $areaSensorMapping = [
            '1' => ['soil_hum1', 'soil_temp1', 'soil_con1', 'soil_ph1', 'soil_nitro1', 'soil_phos1', 'soil_pot1', 'soil_salin1', 'soil_tds1'],
            '2' => ['soil_hum2', 'soil_temp2', 'soil_con2', 'soil_ph2', 'soil_nitro2', 'soil_phos2', 'soil_pot2', 'soil_salin2', 'soil_tds2'],
            '3' => ['soil_hum3', 'soil_temp3', 'soil_con3', 'soil_ph3', 'soil3_nitro', 'soil3_phos', 'soil3_pot', 'soil3_salin', 'soil3_tds'],
            '4' => ['soil_hum4', 'soil_temp4', 'soil_con4', 'soil_ph4', 'soil4_nitro', 'soil4_phos', 'soil4_pot', 'soil4_salin', 'soil4_tds'],
            '5' => ['soil_hum5', 'soil_temp5', 'soil_con5', 'soil_ph5', 'soil5_nitro', 'soil5_phos', 'soil5_pot', 'soil5_salin', 'soil5_tds'],
            '6' => ['soil_hum6', 'soil_temp6', 'soil_con6', 'soil_ph6', 'soil6_nitro', 'soil6_phos', 'soil6_pot', 'soil6_salin', 'soil6_tds'],
            'lingkungan' => ['env_temp', 'env_hum', 'ilum', 'rain', 'wind'],
        ];

        $targetDsIds = [];
        if (in_array('all', $areas)) {
            foreach ($areaSensorMapping as $areaSensors) {
                $targetDsIds = array_merge($targetDsIds, $areaSensors);
            }
        } else {
            foreach ($areas as $area) {
                if (isset($areaSensorMapping[$area])) {
                    $targetDsIds = array_merge($targetDsIds, $areaSensorMapping[$area]);
                }
            }
        }
        $targetDsIds = array_unique($targetDsIds);

        // 3. Filter Sensor Berdasarkan Site ID
        $devIdsInSite = DB::table('tm_device')
            ->where('site_id', $siteId)
            ->pluck('dev_id')
            ->toArray();

        if (empty($devIdsInSite)) {
            return response()->json(['message' => 'Site ID tidak ditemukan atau tidak memiliki device.'], 404);
        }

        $actualDsIdsInSite = DB::table('td_device_sensors')
            ->whereIn('dev_id', $devIdsInSite)
            ->pluck('ds_id')
            ->toArray();

        $finalSensorsToQuery = array_intersect($targetDsIds, $actualDsIdsInSite);

        if (!empty($selectedSensors)) {
            $finalSensorsToQuery = array_intersect($finalSensorsToQuery, $selectedSensors);
        }

        if (empty($finalSensorsToQuery)) {
            return response()->json(['message' => 'Tidak ada sensor yang valid untuk kombinasi site, area, dan jenis sensor yang diminta.'], 404);
        }

        // 4. Tentukan Format Grup Berdasarkan Interval
        $dateFormat = '';
        switch ($interval) {
            case 'hour':
                $dateFormat = '%Y-%m-%d %H:00:00'; // Group per jam
                break;
            case 'day':
                $dateFormat = '%Y-%m-%d'; // Group per hari
                break;
            case 'week':
                $dateFormat = '%Y-%u'; // Group per minggu (tahun-nomor_minggu)
                break;
            case 'month':
                $dateFormat = '%Y-%m'; // Group per bulan
                break;
            default:
                return response()->json(['message' => 'Interval waktu tidak valid.'], 400);
        }

        // 5. Lakukan Query Agregasi ke Database (DIOPTIMALKAN)
        $rawSummaryData = DB::table('tm_sensor_read')
            ->select(
                'ds_id', // Tambahkan ds_id ke SELECT
                DB::raw("DATE_FORMAT(read_date, '{$dateFormat}') as period"),
                DB::raw('AVG(read_value) as average_value'),
                DB::raw('MIN(read_value) as min_value'),
                DB::raw('MAX(read_value) as max_value')
            )
            ->whereIn('ds_id', $finalSensorsToQuery) // Query semua sensor sekaligus
            ->whereIn('dev_id', $devIdsInSite)
            ->whereBetween('read_date', [$carbonStartDate, $carbonEndDate])
            ->groupBy('ds_id', DB::raw("DATE_FORMAT(read_date, '{$dateFormat}')")) // Group by ds_id dan period
            ->orderBy('ds_id', 'ASC')
            ->orderBy('period', 'ASC')
            ->get();

        $summaryData = [];

        // OPTIMASI BARU: Ambil semua nama sensor yang dibutuhkan dalam satu kueri
        $uniqueDsIdsInSummary = $rawSummaryData->pluck('ds_id')->unique()->toArray();
        $sensorNamesMap = DB::table('td_device_sensors')
                            ->whereIn('ds_id', $uniqueDsIdsInSummary)
                            ->pluck('ds_name', 'ds_id')
                            ->toArray();

        // Mengorganisir hasil query menjadi struktur per sensor
        foreach ($rawSummaryData as $dataItem) {
            $dsId = $dataItem->ds_id;
            // Jika sensor belum ada di summaryData, inisialisasi
            if (!isset($summaryData[$dsId])) {
                // Ambil nama sensor dari map yang sudah diambil di awal
                $sensorName = $sensorNamesMap[$dsId] ?? $dsId;
                
                $summaryData[$dsId] = [
                    'ds_id' => $dsId,
                    'sensor_name' => $sensorName,
                    'summary_by_interval' => collect() // Gunakan collection untuk memudahkan push
                ];
            }
            // Tambahkan data periodik ke sensor yang sesuai
            $summaryData[$dsId]['summary_by_interval']->push([
                'period' => $dataItem->period,
                'average_value' => $dataItem->average_value,
                'min_value' => $dataItem->min_value,
                'max_value' => $dataItem->max_value,
            ]);
        }

        // Konversi collection menjadi array untuk respons JSON
        $summaryData = array_values($summaryData);

        // Simpan hasil ke cache sebelum mengembalikan
        // Cache selama 60 menit (Anda bisa sesuaikan sesuai kebutuhan)
        Cache::put($cacheKey, $summaryData, now()->addMinutes(60));
        Log::info('✅ Data ringkasan disimpan ke cache.');

        // 6. Berikan Respons API
        if (empty($summaryData)) {
            return response()->json(['message' => 'Tidak ada data ringkasan untuk rentang waktu, site, area, dan sensor yang dipilih.'], 404);
        }

        return response()->json($summaryData, 200, [], JSON_PRETTY_PRINT);
    }
}