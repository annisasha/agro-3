<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIService
{
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
        $this->model = config('services.openai.model');
    }

    public function ask($message)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'Kamu adalah asisten pertanian cerdas yang menjelaskan data dan informasi dengan ramah dan sederhana.'],
                ['role' => 'user', 'content' => $message],
            ],
        ]);

        if ($response->successful()) {
            return $response->json()['choices'][0]['message']['content'];
        }

        return "Maaf, terjadi kesalahan saat memproses permintaan.";
    }
}