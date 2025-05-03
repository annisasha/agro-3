<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $apiKey = env('OPENAI_API_KEY');

        $payload = [
            'model'    => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => 'Kamu adalah asisten pertanian yang menjelaskan data dengan ramah dan mudah dimengerti.',
                ],
                [
                    'role'    => 'user',
                    'content' => $request->message,
                ],
            ],
        ];

        $response = Http::withToken($apiKey)
                        ->withOptions(['verify' => false])  // non-aktifkan SSL verify sementara
                        ->post('https://api.openai.com/v1/chat/completions', $payload);

        if (! $response->successful()) {
            return response()->json([
                'error' => 'OpenAI request failed',
                'details' => $response->body(),
            ], $response->status());
        }

        $data = $response->json();

        return response()->json([
            'question' => $request->message,
            'response' => $data['choices'][0]['message']['content'] ?? 'Tidak ada balasan.',
        ]);
    }
}