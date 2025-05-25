<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use App\Models\Chatbot;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\RingkasanDataController;
use Illuminate\Support\Facades\Auth;
use App\Models\Device;

class ChatbotDataController extends Controller
{
    public function sendDataQueryWithAuth(Request $request, string $siteId, string $userId): JsonResponse
    {
        $request->validate([
            'message'    => 'required|string',
            'name_chat'  => 'nullable|string|max:64',
        ]);

        $userMessage = strtolower($request->message);
        $reply = 'Maaf, saya tidak bisa memahami permintaan data Anda. Mohon lebih spesifik atau coba format yang berbeda.';
        $nameChat = $request->name_chat ?? Str::limit($userMessage, 40, '...');

        try {
            $token = $request->bearerToken();

            $reply = $this->handleDataQueryLogic($userMessage, $siteId, $userId, $token);

            Chatbot::create([
                'user_id'   => $userId,
                'name_chat' => $nameChat,
                'message'   => $request->message,
                'response'  => $reply,
            ]);

            return response()->json([
                'name_chat' => $nameChat,
                'message'   => $request->message,
                'response'  => $reply,
            ]);
        } catch (\Exception $e) {
            Log::error("KawalTani Data Chat Error: " . $e->getMessage() . " at " . $e->getFile() . " on line " . $e->getLine());
            return response()->json([
                'error' => 'Terjadi kesalahan internal saat memproses permintaan data KawalTani. Silakan coba lagi nanti.',
            ], 500);
        }
    }

    public function isKawalTaniDataQuery(string $message): bool
    {
        // Fungsi ini tidak lagi menjadi filter utama di ChatbotController,
        // tapi jika dipanggil dari tempat lain, biarkan saja.
        if (Str::contains($message, ['ringkasan data', 'rekap data', 'laporan data', 'data historis', 'data dari', 'per hari', 'per minggu', 'per bulan', 'per jam'])) {
            return true;
        }
        if (Str::contains($message, ['data saat ini', 'kondisi lahan sekarang', 'berapa nilai', 'update terbaru', 'realtime', 'data terkini'])) {
            return true;
        }
        if (Str::contains($message, ['unsur hara', 'nitrogen', 'fosfor', 'kalium', 'ph tanah', 'kelembapan tanah', 'suhu tanah', 'tds', 'ec', 'konduktivitas', 'salinitas', 'suhu lingkungan', 'kelembapan lingkungan', 'cahaya', 'angin', 'hujan'])) {
            return true;
        }
        return false;
    }

    private function handleDataQueryLogic(string $userMessage, string $siteId, string $userId, ?string $token = null): string
    {
        if (empty($siteId)) {
            return "Internal Error: Site ID tidak tersedia untuk memproses permintaan data KawalTani.";
        }

        $startDate = null;
        $endDate = null;
        $interval = null;
        $currentYear = date('Y');
        $monthMap = [
            'januari' => '01',
            'februari' => '02',
            'maret' => '03',
            'april' => '04',
            'mei' => '05',
            'juni' => '06',
            'juli' => '07',
            'agustus' => '08',
            'september' => '09',
            'oktober' => '10',
            'november' => '11',
            'desember' => '12',
        ];

        // --- Ekstraksi Rentang Tanggal & Interval ---
        // Pengecekan untuk "setiap bulan" atau "per bulan" tanpa rentang tanggal spesifik
        if (Str::contains($userMessage, ['setiap bulan', 'tiap bulan', 'bulanan'])) {
            $interval = 'month';
            // Default ke seluruh tahun berjalan
            $startDate = Carbon::createFromDate($currentYear, 1, 1)->startOfDay()->format('Y-m-d H:i:s');
            $endDate = Carbon::createFromDate($currentYear, 12, 31)->endOfDay()->format('Y-m-d H:i:s');
        } elseif (Str::contains($userMessage, 'per hari')) {
            $interval = 'day';
        } elseif (Str::contains($userMessage, 'per minggu')) {
            $interval = 'week';
        } elseif (Str::contains($userMessage, 'per jam')) {
            $interval = 'hour';
        }


        // Ekstraksi Tanggal: Mencoba berbagai format (logika yang sudah ada)
        preg_match('/dari\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s+sampai\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/', $userMessage, $matchesFullDateTimeRange);
        if (count($matchesFullDateTimeRange) == 3) {
            $startDate = $matchesFullDateTimeRange[1];
            $endDate = $matchesFullDateTimeRange[2];
            $interval = $interval ?? 'day';
        } else {
            preg_match('/dari\s+(\d{4}-\d{2}-\d{2})\s+sampai\s+(\d{4}-\d{2}-\d{2})/', $userMessage, $matchesDateRange);
            if (count($matchesDateRange) == 3) {
                $startDate = $matchesDateRange[1] . ' 00:00:00';
                $endDate = $matchesDateRange[2] . ' 23:59:59';
                $interval = $interval ?? 'day';
            } else {
                preg_match('/tanggal\s+(\d{1,2}(?:-\d{1,2})?(?:-\d{4})?)/', $userMessage, $matchesSingleDate);
                if (count($matchesSingleDate) == 2) {
                    $datePart = $matchesSingleDate[1];
                    try {
                        $parsedDate = Carbon::parse($datePart);
                        $startDate = $parsedDate->startOfDay()->format('Y-m-d H:i:s');
                        $endDate = $parsedDate->endOfDay()->format('Y-m-d H:i:s');
                        $interval = $interval ?? 'day';
                    } catch (\Exception $e) {
                        Log::warning("Could not parse single date: " . $datePart . " - " . $e->getMessage());
                    }
                } else {
                    $monthNamesPattern = implode('|', array_keys($monthMap));
                    preg_match("/dari\\s+(?:bulan\\s+)?($monthNamesPattern)\\s+sampai\\s+(?:bulan\\s+)?($monthNamesPattern)/", $userMessage, $matchesMonthRange);
                    if (count($matchesMonthRange) == 3 && isset($monthMap[$matchesMonthRange[1]]) && isset($monthMap[$matchesMonthRange[2]])) {
                        $startMonthNum = $monthMap[$matchesMonthRange[1]];
                        $endMonthNum = $monthMap[$matchesMonthRange[2]];
                        $startDate = Carbon::parse("$currentYear-$startMonthNum-01")->startOfDay()->format('Y-m-d H:i:s');
                        $endDate = Carbon::parse("$currentYear-$endMonthNum-01")->endOfMonth()->format('Y-m-d H:i:s');
                        $interval = $interval ?? 'month';
                    } else {
                        preg_match("/bulan\\s+($monthNamesPattern)/", $userMessage, $matchesSingleMonth);
                        if (count($matchesSingleMonth) == 2 && isset($monthMap[$matchesSingleMonth[1]])) {
                            $singleMonthNum = $monthMap[$matchesSingleMonth[1]];
                            $startDate = Carbon::parse("$currentYear-$singleMonthNum-01")->startOfDay()->format('Y-m-d H:i:s');
                            $endDate = Carbon::parse("$currentYear-$singleMonthNum-01")->endOfMonth()->format('Y-m-d H:i:s');
                            $interval = $interval ?? 'month';
                        } else {
                            if (Str::contains($userMessage, 'hari ini')) {
                                $startDate = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
                                $endDate = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');
                                $interval = $interval ?? 'day';
                            } elseif (Str::contains($userMessage, 'kemarin')) {
                                $startDate = Carbon::yesterday()->startOfDay()->format('Y-m-d H:i:s');
                                $endDate = Carbon::yesterday()->endOfDay()->format('Y-m-d H:i:s');
                                $interval = $interval ?? 'day';
                            }
                        }
                    }
                }
            }
        }


        // 2. Ekstraksi Jenis Sensor yang Diminta
        // Di sini kita hanya butuh nama dasar/prefix sensor (misal: 'soil_nitro', 'env_temp')
        $requestedSensorTypes = [];
        if (Str::contains($userMessage, 'nitrogen') || Str::contains($userMessage, 'n')) $requestedSensorTypes[] = 'soil_nitro';
        if (Str::contains($userMessage, 'fosfor') || Str::contains($userMessage, 'p')) $requestedSensorTypes[] = 'soil_phos';
        if (Str::contains($userMessage, 'kalium') || Str::contains($userMessage, 'k')) $requestedSensorTypes[] = 'soil_pot';
        if (Str::contains($userMessage, 'ph tanah') || Str::contains($userMessage, 'ph')) $requestedSensorTypes[] = 'soil_ph';
        if (Str::contains($userMessage, 'kelembapan tanah') || Str::contains($userMessage, 'kelembaban tanah') || Str::contains($userMessage, 'tanah lembap')) $requestedSensorTypes[] = 'soil_hum';
        if (Str::contains($userMessage, 'suhu tanah')) $requestedSensorTypes[] = 'soil_temp';
        if (Str::contains($userMessage, 'tds')) $requestedSensorTypes[] = 'soil_tds';
        if (Str::contains($userMessage, 'ec') || Str::contains($userMessage, 'konduktivitas')) $requestedSensorTypes[] = 'soil_con';
        if (Str::contains($userMessage, 'salinitas')) $requestedSensorTypes[] = 'soil_salin';
        // Untuk sensor lingkungan, ini harus sesuai dengan prefix ds_id yang dikembalikan RealtimeController
        if (Str::contains($userMessage, 'suhu lingkungan') || Str::contains($userMessage, 'suhu udara')) $requestedSensorTypes[] = 'env_temp'; // ds_id env_temp
        if (Str::contains($userMessage, 'kelembapan lingkungan') || Str::contains($userMessage, 'kelembaban udara')) $requestedSensorTypes[] = 'env_hum'; // ds_id env_hum
        if (Str::contains($userMessage, 'cahaya') || Str::contains($userMessage, 'iluminasi') || Str::contains($userMessage, 'lux')) $requestedSensorTypes[] = 'ilum'; // ds_id ilum (no env_ prefix here based on user list)
        if (Str::contains($userMessage, 'angin') || Str::contains($userMessage, 'kecepatan angin')) $requestedSensorTypes[] = 'wind'; // ds_id wind (no env_ prefix here based on user list)
        if (Str::contains($userMessage, 'hujan') || Str::contains($userMessage, 'curah hujan')) $requestedSensorTypes[] = 'rain'; // ds_id rain (no env_ prefix here based on user list)

        if (Str::contains($userMessage, 'unsur hara') && empty($requestedSensorTypes)) {
            // Default semua sensor tanah jika hanya 'unsur hara' disebut
            $requestedSensorTypes = ['soil_nitro', 'soil_phos', 'soil_pot', 'soil_ph', 'soil_hum', 'soil_tds', 'soil_con', 'soil_temp', 'soil_salin'];
        }
        if (empty($requestedSensorTypes)) {
            // Default yang lebih umum jika tidak ada sensor spesifik yang diminta
            $requestedSensorTypes = ['soil_nitro', 'soil_phos', 'soil_pot', 'env_temp', 'env_hum'];
        }

        // 3. Ekstraksi Area
        $requestedAreas = ['all'];
        if (Str::contains($userMessage, 'area 1')) $requestedAreas = ['1'];
        else if (Str::contains($userMessage, 'area 2')) $requestedAreas = ['2'];
        else if (Str::contains($userMessage, 'area 3')) $requestedAreas = ['3'];
        else if (Str::contains($userMessage, 'area 4')) $requestedAreas = ['4'];
        else if (Str::contains($userMessage, 'area 5')) $requestedAreas = ['5'];
        else if (Str::contains($userMessage, 'area 6')) $requestedAreas = ['6'];
        else if (Str::contains($userMessage, 'area lingkungan') || Str::contains($userMessage, 'area cuaca')) $requestedAreas = ['lingkungan'];


        // 4. Panggil API Backend Berdasarkan Tipe Query
        $isRealtimeQuery = Str::contains($userMessage, ['data saat ini', 'kondisi lahan sekarang', 'berapa nilai', 'update terbaru', 'realtime', 'data terkini']);

        if ($isRealtimeQuery && !$startDate && !$endDate) {
            return $this->fetchAndSummarizeRealtimeData($siteId, $requestedSensorTypes, $token);
        } elseif ($startDate && $endDate && $interval) {
            return $this->fetchAndSummarizeDataByInterval($siteId, $requestedAreas, $requestedSensorTypes, $startDate, $endDate, $interval, $token);
        } else {
            return "Maaf, saya mengerti Anda ingin data KawalTani, tetapi saya tidak bisa mengekstrak informasi yang spesifik (rentang waktu, interval, atau jenis data). Mohon coba lagi. Contoh: 'data fosfor tanggal 2024-05-20', 'ringkasan suhu lingkungan bulan Mei per hari', atau 'data kelembapan tanah saat ini'.";
        }
    }

    private function fetchAndSummarizeDataByInterval(string $siteId, array $areas, array $sensorTypes, string $startDate, string $endDate, string $interval, ?string $token = null): string
    {
        // === UPDATE MAPPING INI SESUAI DS_ID YANG ANDA BERIKAN ===
        $areaSensorMappingForChatbot = [
            '1' => ['soil_hum1', 'soil_temp1', 'soil_con1', 'soil_ph1', 'soil_nitro1', 'soil_phos1', 'soil_pot1', 'soil_salin1', 'soil_tds1'],
            '2' => ['soil_hum2', 'soil_temp2', 'soil_con2', 'soil_ph2', 'soil_nitro2', 'soil_phos2', 'soil_pot2', 'soil_salin2', 'soil_tds2'],
            '3' => ['soil_hum3', 'soil_temp3', 'soil_con3', 'soil_ph3', 'soil3_nitro', 'soil3_phos', 'soil3_pot', 'soil3_salin', 'soil3_tds'],
            '4' => ['soil_hum4', 'soil_temp4', 'soil_con4', 'soil_ph4', 'soil4_nitro', 'soil4_phos', 'soil4_pot', 'soil4_salin', 'soil4_tds'],
            '5' => ['soil_hum5', 'soil_temp5', 'soil_con5', 'soil_ph5', 'soil5_nitro', 'soil5_phos', 'soil5_pot', 'soil5_salin', 'soil5_tds'],
            '6' => ['soil_hum6', 'soil_temp6', 'soil_con6', 'soil_ph6', 'soil6_nitro', 'soil6_phos', 'soil6_pot', 'soil6_salin', 'soil6_tds'],
            'lingkungan' => ['env_temp', 'env_hum', 'ilum', 'rain', 'wind'], // 'ilum', 'rain', 'wind' without 'env_' prefix as per user's list
        ];
        // =========================================================

        $sensorsToFetch = [];
        if (in_array('all', $areas)) {
            $allPossibleAreas = array_keys($areaSensorMappingForChatbot);
            foreach ($allPossibleAreas as $area) {
                if (isset($areaSensorMappingForChatbot[$area])) {
                    foreach ($areaSensorMappingForChatbot[$area] as $ds_id_in_area) {
                        foreach ($sensorTypes as $type) {
                            if (Str::contains($ds_id_in_area, $type)) {
                                $sensorsToFetch[] = $ds_id_in_area;
                                break;
                            }
                        }
                    }
                }
            }
        } else {
            foreach ($areas as $area) {
                if (isset($areaSensorMappingForChatbot[$area])) {
                    foreach ($areaSensorMappingForChatbot[$area] as $ds_id_in_area) {
                        foreach ($sensorTypes as $type) {
                            if (Str::contains($ds_id_in_area, $type)) {
                                $sensorsToFetch[] = $ds_id_in_area;
                                break;
                            }
                        }
                    }
                }
            }
        }
        $sensorsToFetch = array_unique($sensorsToFetch);

        if (empty($sensorsToFetch)) {
            return "Maaf, saya tidak dapat menemukan sensor yang relevan dengan pertanyaan Anda untuk Site ID Anda. Pastikan nama sensor (misal: 'kelembapan tanah', 'fosfor', 'suhu lingkungan') dan area (misal: 'area 1', 'area lingkungan') yang Anda sebutkan valid.";
        }


        $http = Http::baseUrl(url('/api'));
        if ($token) {
            $http->withToken($token);
        }

        $apiResponse = $http->post('/data/summary', [
            'site_id' => $siteId,
            'areas' => $areas,
            'sensors' => $sensorsToFetch,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'interval' => $interval,
        ]);

        if ($apiResponse->successful()) {
            $data = $apiResponse->json();
            if (!empty($data)) {
                $responseString = "Berikut adalah ringkasan data KawalTani dari " . Carbon::parse($startDate)->translatedFormat('d F Y H:i') . " hingga " . Carbon::parse($endDate)->translatedFormat('d F Y H:i') . " per **$interval** untuk Site ID **$siteId**:\n\n";

                foreach ($data as $sensorSummary) {
                    $sensorDisplayName = $sensorSummary['sensor_name'] ?? $sensorSummary['ds_id'];
                    $responseString .= "--- **" . $sensorDisplayName . "** ---\n";
                    foreach ($sensorSummary['summary_by_interval'] as $periodData) {
                        $periodLabel = $periodData['period'];
                        if ($interval === 'month') {
                            $periodLabel = Carbon::parse($periodData['period'] . '-01')->translatedFormat('F Y');
                        } elseif ($interval === 'week') {
                            $year = substr($periodData['period'], 0, 4);
                            $week = substr($periodData['period'], 5);
                            $periodLabel = "Minggu " . intval($week) . ", $year";
                        } elseif ($interval === 'day') {
                            $periodLabel = Carbon::parse($periodData['period'])->translatedFormat('d F Y');
                        } elseif ($interval === 'hour') {
                            $periodLabel = Carbon::parse($periodData['period'])->translatedFormat('d F Y H:i');
                        }

                        $responseString .= "Periode **" . $periodLabel . "**:\n";
                        $responseString .= "  Rata-rata: " . round($periodData['average_value'], 2) . "\n";
                        $responseString .= "  Min: " . round($periodData['min_value'], 2) . "\n";
                        $responseString .= "  Max: " . round($periodData['max_value'], 2) . "\n\n";
                    }
                }
                return $responseString;
            } else {
                $datePeriod = '';
                if ($startDate && $endDate) {
                    $startCarbon = Carbon::parse($startDate);
                    $endCarbon = Carbon::parse($endDate);
                    if ($startCarbon->isSameDay($endCarbon)) {
                        $datePeriod = 'pada tanggal ' . $startCarbon->translatedFormat('d F Y');
                    } else {
                        $datePeriod = 'dari tanggal ' . $startCarbon->translatedFormat('d F Y') . ' hingga ' . $endCarbon->translatedFormat('d F Y');
                    }
                }
                $sensorNames = implode(', ', array_map(function ($type) {
                    if (Str::startsWith($type, 'soil_hum')) return 'kelembapan tanah';
                    if (Str::startsWith($type, 'soil_temp')) return 'suhu tanah';
                    if (Str::startsWith($type, 'soil_con')) return 'konduktivitas tanah';
                    if (Str::startsWith($type, 'soil_ph')) return 'PH tanah';
                    if (Str::startsWith($type, 'soil_nitro')) return 'nitrogen tanah';
                    if (Str::startsWith($type, 'soil_phos')) return 'fosfor tanah';
                    if (Str::startsWith($type, 'soil_pot')) return 'kalium tanah';
                    if (Str::startsWith($type, 'soil_salin')) return 'salinitas tanah';
                    if (Str::startsWith($type, 'soil_tds')) return 'TDS tanah';
                    if (Str::startsWith($type, 'env_temp')) return 'suhu lingkungan';
                    if (Str::startsWith($type, 'env_hum')) return 'kelembapan lingkungan';
                    if (Str::startsWith($type, 'ilum')) return 'kecerahan lingkungan';
                    if (Str::startsWith($type, 'rain')) return 'curah hujan lingkungan';
                    if (Str::startsWith($type, 'wind')) return 'kecepatan angin lingkungan';
                    return $type;
                }, $sensorTypes));

                return "Maaf, tidak ada data $sensorNames $datePeriod untuk Site ID $siteId yang ditemukan. Pastikan data tersedia di database untuk periode tersebut.";
            }
        } else {
            Log::error("Failed to fetch summary data from backend: " . $apiResponse->body());
            return "Maaf, saya gagal mengambil data ringkasan dari sistem KawalTani. Silakan coba lagi nanti. Error: " . ($apiResponse->json()['message'] ?? 'Terjadi kesalahan tidak dikenal.');
        }
    }

    private function fetchAndSummarizeRealtimeData(string $siteId, array $requestedSensorTypes, ?string $token = null): string
    {
        $http = Http::baseUrl(url('/api'));
        if ($token) {
            $http->withToken($token);
        }

        $apiResponse = $http->get('/realtime', [
            'site_id' => $siteId,
        ]);

        if ($apiResponse->successful()) {
            $data = $apiResponse->json();
            $responseString = "Data terkini dari lahan Anda (Site ID: **$siteId**) adalah:\n\n";
            $foundData = false;

            if (isset($data['sensors']) && is_array($data['sensors'])) {
                foreach ($data['sensors'] as $sensorItem) {
                    $sensorDsId = $sensorItem['sensor'];
                    foreach ($requestedSensorTypes as $type) {
                        if (Str::startsWith($sensorDsId, $type)) {
                            $responseString .= "  - **" . ($sensorItem['sensor_name'] ?? $sensorDsId) . ": " . $sensorItem['read_value'] . " (" . $sensorItem['value_status'] . "). " . ($sensorItem['action_message'] ?? '') . "\n";
                            $foundData = true;
                            break;
                        }
                    }
                }
            }


            if (!$foundData) {
                $sensorNames = implode(', ', array_map(function ($type) {
                    if (Str::startsWith($type, 'soil_hum')) return 'kelembapan tanah';
                    if (Str::startsWith($type, 'soil_temp')) return 'suhu tanah';
                    if (Str::startsWith($type, 'soil_con')) return 'konduktivitas tanah';
                    if (Str::startsWith($type, 'soil_ph')) return 'PH tanah';
                    if (Str::startsWith($type, 'soil_nitro')) return 'nitrogen tanah';
                    if (Str::startsWith($type, 'soil_phos')) return 'fosfor tanah';
                    if (Str::startsWith($type, 'soil_pot')) return 'kalium tanah';
                    if (Str::startsWith($type, 'soil_salin')) return 'salinitas tanah';
                    if (Str::startsWith($type, 'soil_tds')) return 'TDS tanah';
                    if (Str::startsWith($type, 'env_temp')) return 'suhu lingkungan';
                    if (Str::startsWith($type, 'env_hum')) return 'kelembapan lingkungan';
                    if (Str::startsWith($type, 'ilum')) return 'kecerahan lingkungan';
                    if (Str::startsWith($type, 'rain')) return 'curah hujan lingkungan';
                    if (Str::startsWith($type, 'wind')) return 'kecepatan angin lingkungan';
                    return $type;
                }, $requestedSensorTypes));
                return "Tidak ada data terkini $sensorNames yang ditemukan untuk Site ID: $siteId. Mungkin nama sensor yang Anda sebutkan salah atau tidak ada data terbaru.";
            }

            $responseString .= "\nTerakhir diperbarui: " . ($data['last_updated'] ?? 'N/A') . " WIB.";
            return $responseString;
        } else {
            Log::error("Failed to fetch realtime data from backend: " . $apiResponse->body());
            return "Maaf, saya gagal mengambil data terkini dari sistem KawalTani. Silakan coba lagi nanti. Error: " . ($apiResponse->json()['message'] ?? 'Terjadi kesalahan tidak dikenal.');
        }
    }
}
