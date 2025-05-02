<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chatbot extends Model
{
    protected $fillable = ['message', 'response'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
