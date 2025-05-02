<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAIService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('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'); 
    }

    public function ask(string $message): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'Kamu adalah asisten virtual agrikultur yang membantu petani memahami data lahan.'],
                ['role' => 'user', 'content' => $message],
            ],
        ]);

        if ($response->successful()) {
            return $response->json()['choices'][0]['message']['content'];
        }

        return "Maaf, saya tidak dapat memproses permintaan saat ini.";
    }
}