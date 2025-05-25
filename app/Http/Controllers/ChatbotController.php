<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Chatbot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Http\Controllers\ChatbotDataController;
use App\Models\Device; // Tambahkan ini
use App\Models\Site; // Tambahkan ini

class ChatbotController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'message'    => 'required|string',
            'name_chat'  => 'nullable|string|max:64',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated. Silakan login terlebih dahulu.'], 401);
        }

        $userMessage = strtolower($request->message);
        $nameChat = $request->name_chat ?? Str::limit($request->message, 40, '...');
        $originalMessage = $request->message;
        $userId = $user->user_id;

        $siteId = $this->getSiteIdByUserId($userId);

        try {
            // Cek apakah ini pertanyaan umum MURNI (misal: "Apa itu fosfor?")
            if ($this->isGeneralQuery($userMessage)) {
                $reply = $this->sendGeneralMessageToOpenAI($originalMessage);
                Chatbot::create([
                    'user_id'   => $userId,
                    'name_chat' => $nameChat,
                    'message'   => $originalMessage,
                    'response'  => $reply,
                ]);
                return response()->json([
                    'name_chat' => $nameChat,
                    'message'   => $originalMessage,
                    'response'  => $reply,
                ]);
            }

            // Jika bukan pertanyaan umum murni, coba deteksi apakah ini pertanyaan DATA
            if ($this->isDataQuery($userMessage)) {
                if ($siteId) {
                    $chatbotData = new ChatbotDataController();
                    return $chatbotData->sendDataQueryWithAuth($request, $siteId, $userId);
                } else {
                    $responseContent = "Maaf, saya tidak dapat menemukan Site ID yang terkait dengan akun Anda, sehingga saya tidak bisa memberikan data spesifik. Saya hanya bisa menjawab pertanyaan pertanian umum.";
                    Chatbot::create([
                        'user_id'   => $userId,
                        'name_chat' => $nameChat,
                        'message'   => $originalMessage,
                        'response'  => $responseContent,
                    ]);
                    return response()->json([
                        'name_chat' => $nameChat,
                        'message'   => $originalMessage,
                        'response'  => $responseContent,
                    ]);
                }
            }

            // Fallback: Jika tidak terdeteksi sebagai pertanyaan umum murni atau data,
            // kirim ke OpenAI. Ini akan menangani pertanyaan yang ambigu atau di luar cakupan.
            $reply = $this->sendGeneralMessageToOpenAI($originalMessage);
            Chatbot::create([
                'user_id'   => $userId,
                'name_chat' => $nameChat,
                'message'   => $originalMessage,
                'response'  => $reply,
            ]);
            return response()->json([
                'name_chat' => $nameChat,
                'message'   => $originalMessage,
                'response'  => $reply,
            ]);
        } catch (\Exception $e) {
            Log::error("Chatbot Controller Error: " . $e->getMessage() . " at " . $e->getFile() . " on line " . $e->getLine());
            return response()->json([
                'error' => 'Terjadi kesalahan pada sistem chatbot KawalTani. Silakan coba lagi nanti.',
            ], 500);
        }
    }

    private function sendGeneralMessageToOpenAI(string $message): string
    {
        try {
            $responseOpenAI = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'Kamu adalah asisten pertanian cerdas bernama KawalTani. Berikan informasi umum, tips, dan edukasi seputar pertanian. Jawablah dengan ramah dan informatif.'],
                    ['role' => 'user', 'content' => $message],
                ],
            ]);

            return $responseOpenAI['choices'][0]['message']['content'] ?? 'Tidak ada balasan dari AI KawalTani untuk pertanyaan umum.';
        } catch (\Exception $e) {
            Log::error("OpenAI Call Error in ChatbotController: " . $e->getMessage());
            return 'Maaf, saya KawalTani sedang mengalami masalah untuk menghubungi AI utama. Silakan coba lagi nanti.';
        }
    }

    private function getSiteIdByUserId(string $userId): ?string
    {
        $siteId = Device::where('user_id', $userId)
            ->value('site_id');
        return $siteId;
    }

    /**
     * Membedakan pertanyaan umum (definisi, cara, fungsi) dari pertanyaan data.
     * Logika ini HARUS lebih ketat agar tidak salah mengidentifikasi pertanyaan data.
     * @param string $message
     * @return bool
     */
    // Controllers/ChatbotController.php

    private function isGeneralQuery(string $message): bool
    {
        $generalKeywords = [
            'apa itu',
            'apa ya',
            'apa maksud',
            'bagaimana',
            'cara',
            'fungsi',
            'manfaat',
            'definisi',
            'penjelasan',
            'terangkan',
            'jelaskan',
            'kenapa',
            'mengapa',
            // Tambahkan keyword untuk pertanyaan berbasis aksi/saran
            'saya harus ngapain',
            'apa yang harus saya lakukan',
            'solusi',
            'mengatasi',
            'jika',
            'apabila',
            'rekomendasi',
            'saran',
            'penanganan' //
        ];

        // Indikator kuat bahwa ini adalah pertanyaan data, bukan pertanyaan umum murni
        $strongDataIndicators = [
            'data',
            'nilai',
            'berapa',
            'laporan',
            'ringkasan',
            'rekap',
            'historis',
            'terkini',
            'saat ini',
            'hari ini',
            'kemarin',
            'tanggal',
            'dari',
            'sampai',
            'per hari',
            'per minggu',
            'per bulan',
            'per jam'
        ];

        foreach ($generalKeywords as $keyword) {
            if (Str::contains($message, $keyword)) {
                // Jika ada kata kunci umum, sekarang cek apakah ada indikator data yang kuat
                foreach ($strongDataIndicators as $dataIndicator) {
                    if (Str::contains($message, $dataIndicator)) {
                        return false; // Ada indikator data yang kuat, jadi bukan general query murni
                    }
                }
                // Jika tidak ada indikator data yang kuat, dan ada kata kunci umum
                return true;
            }
        }
        return false;
    }

    /**
     * Mendeteksi apakah pesan adalah permintaan data spesifik KawalTani.
     * @param string $message
     * @return bool
     */
    private function isDataQuery(string $message): bool
    {
        // Kata kunci untuk ringkasan historis
        if (Str::contains($message, ['ringkasan data', 'rekap data', 'laporan data', 'data historis', 'data dari', 'per hari', 'per minggu', 'per bulan', 'per jam'])) {
            return true;
        }
        // Kata kunci untuk data realtime
        if (Str::contains($message, ['data saat ini', 'kondisi lahan sekarang', 'berapa nilai', 'update terbaru', 'realtime', 'data terkini'])) {
            return true;
        }

        // Kata kunci sensor. Ini akan dicocokkan dengan `soil_nitro`, `soil_phos`, dll.
        $sensorKeywords = ['nitrogen', 'fosfor', 'kalium', 'ph tanah', 'kelembapan tanah', 'suhu tanah', 'tds', 'ec', 'konduktivitas', 'salinitas', 'cahaya', 'angin', 'hujan'];
        // Untuk "suhu lingkungan" dan "kelembapan lingkungan", mereka juga bisa jadi pemicu data.
        $environmentKeywords = ['suhu lingkungan', 'kelembapan lingkungan', 'suhu udara', 'kelembaban udara'];

        // Gabungkan semua keyword yang mungkin mengindikasikan permintaan data
        $allPossibleDataKeywords = array_merge(
            ['data', 'nilai', 'berapa', 'laporan', 'tanggal', 'hari ini', 'kemarin', 'rata-rata', 'minimum', 'maksimum'],
            $sensorKeywords,
            $environmentKeywords
        );

        foreach ($allPossibleDataKeywords as $keyword) {
            if (Str::contains($message, $keyword)) {
                // Jika mengandung salah satu keyword di atas, kemungkinan besar itu pertanyaan data.
                // Kita tidak perlu cek angka lagi di sini, karena keyword sudah cukup kuat.
                return true;
            }
        }
        return false;
    }


    public function getHistoryByNameChat($nameChat): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $history = Chatbot::where('user_id', $user->user_id)
            ->where('name_chat', $nameChat)
            ->orderBy('created_at')
            ->get();

        return response()->json($history);
    }

    public function deleteByNameChat($nameChat): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Chatbot::where('user_id', $user->user_id)
            ->where('name_chat', $nameChat)
            ->delete();

        return response()->json(['message' => 'Percakapan berhasil dihapus']);
    }

    public function listChats(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $latestChats = Chatbot::selectRaw('MAX(id) as id, name_chat, MAX(created_at) as created_at')
            ->where('user_id', $user->user_id)
            ->groupBy('name_chat')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($latestChats);
    }

    public function renameChat(Request $request, $nameChat): JsonResponse
    {
        $request->validate([
            'newName' => 'required|string|max:64',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (Chatbot::where('user_id', $user->user_id)->where('name_chat', $request->newName)->exists()) {
            return response()->json(['error' => 'Nama chat sudah digunakan oleh Anda'], 422);
        }

        Chatbot::where('user_id', $user->user_id)
            ->where('name_chat', $nameChat)
            ->update(['name_chat' => $request->newName]);

        return response()->json(['message' => 'Nama chat berhasil diganti']);
    }

    public function newChat(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated. Silakan login terlebih dahulu.'], 401);
        }

        $message = $request->input('message');
        $userId = $user->user_id;

        if (!$message || trim($message) === '') {
            return response()->json([
                'name_chat' => '',
                'response' => '',
            ]);
        }

        $chatName = Str::limit($message, 40, '...');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'Kamu adalah asisten pertanian cerdas bernama KawalTani. Berikan informasi umum, tips, dan edukasi seputar pertanian. Jawablah dengan ramah dan informatif.'],
                    ['role' => 'user', 'content' => $message],
                ],
            ]);

            $reply = $response['choices'][0]['message']['content'] ?? 'Tidak ada balasan.';
        } catch (\Exception $e) {
            Log::error("OpenAI Call Error in newChat: " . $e->getMessage());
            $reply = 'Maaf, saya KawalTani sedang mengalami masalah untuk menghubungi AI utama. Silakan coba lagi nanti.';
        }

        Chatbot::create([
            'user_id'   => $userId,
            'name_chat' => $chatName,
            'message'   => $message,
            'response'  => $reply,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'name_chat' => $chatName,
            'response'  => $reply,
        ]);
    }
}