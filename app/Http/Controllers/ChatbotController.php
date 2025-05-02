<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chatbot;
use App\Services\OpenAIService;
use Illuminate\Support\Facades\Auth;

class ChatbotController extends Controller
{
    protected $openAI;

    public function __construct(OpenAIService $openAI)
    {
        $this->openAI = $openAI;
    }

    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string'
        ]);


        $userMessage = $request->input('message');
        $aiResponse = $this->openAI->ask($userMessage);

        $chat = Chatbot::create([
            'message' => $userMessage,
            'response' => $aiResponse,
        ]);

        return response()->json([
            'success' => true,
            'data' => $chat
        ]);
    }

    // public function history()
    // {
    //     $user = Auth::user();

    //     $chats = Chatbot::where('user_id', $user->id)
    //                 ->orderBy('created_at', 'desc')
    //                 ->get();

    //     return response()->json([
    //         'success' => true,
    //         'data' => $chats
    //     ]);
    // }
}