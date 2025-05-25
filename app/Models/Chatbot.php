<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Chatbot extends Model
{
    use HasFactory;

    protected $table = 'chatbot';

    protected $fillable = [
        'user_id',
        'name_chat',
        'message',
        'response',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}